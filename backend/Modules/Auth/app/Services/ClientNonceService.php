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
    private const TOKEN_CREATED = -1;

    /**
     * Redirect has been sent.
     */
    private const REDIRECT_SENT = -2;

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
            $nonce = bin2hex(random_bytes(16));
            $attempts++;

            if ($attempts >= self::MAX_RETRIES) {
                throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
            }
        } while ($this->cache->has($this->getCacheKey($nonce)));

        $this->setNonceValue($nonce, self::TOKEN_CREATED);

        return $this->hmacService->signWithHmac($nonce);
    }

    /**
     * Validate the nonce for redirect.
     *
     * @throws OAuthException
     */
    public function validateNonce(string $signedNonce): string
    {
        $nonce = $this->hmacService->extractAndVerify($signedNonce);

        if (!$nonce) {
            throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
        }

        $key   = $this->getCacheKey($nonce);
        $value = $this->cache->get($key);

        if ($value !== self::TOKEN_CREATED) {
            throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
        }

        // Mark nonce as used
        $this->setNonceValue($nonce, self::REDIRECT_SENT);

        return $nonce;
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
}
