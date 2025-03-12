<?php

namespace Modules\Auth\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;

class ClientNonceService
{
    private const CACHE_KEY_PREFIX = 'client_nonce_';
    private const MAX_RETRIES      = 2;

    /**
     * The flow just started.
     */
    public const TOKEN_CREATED = -1;

    /**
     * Redirect has been sent.
     */
    public const TOKEN_REDIRECTED = -2;

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
        private readonly HmacService $hmacService,
    ) {
    }

    /**
     * Get cache key for a given nonce.
     */
    private function getCacheKey(string $nonce): string
    {
        return self::CACHE_KEY_PREFIX . $nonce;
    }

    /**
     * Creates a new unique client nonce.
     */
    public function create(): string
    {
        $attempts = 0;

        do {
            $nonce = $this->generateRandomNonce();
            $attempts++;

            if ($attempts >= self::MAX_RETRIES) {
                throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
            }
        } while ($this->cache->has($this->getCacheKey($nonce)));

        $this->setNonceValue($nonce, self::TOKEN_CREATED);

        return $this->getSignedNonce($nonce);
    }

    /**
     * Get the signed nonce.
     */
    public function getSignedNonce(string $nonce): string
    {
        return $this->hmacService->signWithHmac($nonce);
    }

    /**
     * Get the raw nonce.
     *
     * @throws OAuthException
     */
    public function getNonce(string $signedNonce, ?int $expectedState = null, $setState = null): string
    {
        $nonce = $this->hmacService->extractAndVerify($signedNonce);

        if (!$nonce) {
            throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
        }

        $key   = $this->getCacheKey($nonce);
        $value = $this->cache->get($key);

        if ($expectedState && $value !== $expectedState) {
            throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
        }

        if ($setState) {
            $this->setNonceValue($nonce, $setState);
        }

        return $nonce;
    }

    /**
     * Get the user ID associated with a client nonce.
     *
     * @throws OAuthException
     */
    public function getUserIdFromNonce(string $nonce): ?int
    {
        $userId = $this->cache->get(
            $this->getCacheKey($nonce),
        );

        if ($userId <= 0) {
            throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
        }

        return $userId;
    }

    /**
     * Forgets a client nonce.
     */
    public function forget(string $nonce): bool
    {
        return $this->cache->forget(
            $this->getCacheKey($nonce),
        );
    }

    public function assignRedirectedToNonce(string $nonce): void
    {
        $this->setNonceValue($nonce, self::TOKEN_REDIRECTED);
    }

    /**
     * Assigns a user ID to a client nonce.
     */
    public function assignUserToNonce(string $nonce, int $userId): void
    {
        $this->setNonceValue($nonce, $userId);
    }

    /**
     * Sets value attached to the nonce in the cache.
     */
    private function setNonceValue(string $nonce, int $userId): void
    {
        $this->cache->put(
            $this->getCacheKey($nonce),
            $userId,
            $this->config->get('auth.oauth.nonce_ttl', 1),
        );
    }

    /**
     * Generate a random nonce.
     *
     * @return string
     */
    private function generateRandomNonce(): string
    {
        return bin2hex(random_bytes(16));
    }
}
