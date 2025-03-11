<?php

namespace Modules\Auth\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;

class ClientNonceService
{
    private const CACHE_KEY_PREFIX = 'client_nonce_';
    private const MAX_RETRIES      = 2;
    private const HMAC_SECRET      = 'your_secure_hmac_key';

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
    ) {
    }

    /**
     * Validate the nonce for redirect.
     *
     * @param string $nonce Signed client nonce.
     * @throws OAuthException
     */
    public function validateNonce(string $nonce): string
    {
        [$nonceValue, $hmac] = explode('.', $nonce, 2);

        if (!$this->verifyNonceHmac($nonceValue, $hmac)) {
            throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
        }

        $key   = $this->getCacheKey($nonceValue);
        $value = $this->cache->get($key);

        if ($value !== self::TOKEN_CREATED) {
            throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
        }

        // Redirect has been sent.
        $this->setNonceValue($nonceValue, self::REDIRECT_SENT);

        return $nonceValue;
    }

    /**
     * Assigns a user ID to a client nonce.
     */
    public function assignUserToNonce(string $nonce, int $userId): void
    {
        [$nonceValue] = explode('.', $nonce, 2);
        $this->setNonceValue($nonceValue, $userId);
    }

    /**
     * Creates a new unique client nonce.
     */
    public function create(): string
    {
        $attempts = 0;

        do {
            $nonce = $this->generateRandomKey();
            $attempts++;

            if ($attempts >= self::MAX_RETRIES) {
                throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
            }
        } while ($this->cache->has($this->getCacheKey($nonce)));

        $this->setNonceValue($nonce, self::TOKEN_CREATED);

        return $this->signNonce($nonce);
    }

    /**
     * Sets value attached to the nonce in the cache.
     */
    private function setNonceValue(string $nonce, int $userId): void
    {
        $this->cache->put(
            $this->getCacheKey($nonce),
            $userId,
            $this->config->get('auth.socialite.nonce_ttl', 1),
        );
    }

    /**
     * Generates a random key.
     */
    private function generateRandomKey(): string
    {
        return bin2hex(random_bytes(10));
    }

    /**
     * Get cache key for a given nonce.
     */
    private function getCacheKey(string $nonce): string
    {
        return self::CACHE_KEY_PREFIX . $nonce;
    }

    /**
     * Sign the nonce with HMAC for verification.
     */
    private function signNonce(string $nonce): string
    {
        $hmac = hash_hmac('sha256', $nonce, self::HMAC_SECRET);
        return "{$nonce}.{$hmac}";
    }

    /**
     * Verify the nonce HMAC to ensure authenticity.
     */
    private function verifyNonceHmac(string $nonce, string $hmac): bool
    {
        $expectedHmac = hash_hmac('sha256', $nonce, self::HMAC_SECRET);
        return hash_equals($expectedHmac, $hmac);
    }
}
