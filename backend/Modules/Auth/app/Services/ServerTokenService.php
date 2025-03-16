<?php

namespace Modules\Auth\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Psr\SimpleCache\InvalidArgumentException;
use Random\RandomException;

class ServerTokenService
{
    private const string CACHE_KEY_PREFIX = 'server_token_';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
        private readonly HmacService $hmacService,
    ) {
    }

    /**
     * Get cache key for a given token.
     */
    private function getCacheKey(string $token): string
    {
        return self::CACHE_KEY_PREFIX . $token;
    }

    /**
     * Create a secure server token and map it to a client nonce.
     * @throws RandomException
     */
    public function create(string $nonce): string
    {
        $serverToken = $this->generateRandomToken();
        $ttl         = $this->config->get('auth.oauth.token_ttl', 1);

        $this->cache->put(
            $this->getCacheKey($serverToken),
            $nonce,
            $ttl,
        );

        return $this->getSignedToken($serverToken);
    }

    /**
     * Generate a random token.
     * @throws RandomException
     */
    public function generateRandomToken(): string
    {
        return bin2hex(random_bytes(64));
    }

    /**
     * Get the signed token.
     */
    public function getSignedToken(string $token): string
    {
        return $this->hmacService->signWithHmac($token);
    }

    /**
     * Retrieve client nonce from server token.
     * @throws InvalidArgumentException
     */
    public function getClientNonce(string $signedServerToken): ?string
    {
        $serverToken = $this->hmacService->extractAndVerify($signedServerToken);

        if (empty($serverToken)) {
            return null;
        }

        return $this->cache->get(
            $this->getCacheKey($serverToken),
        );
    }

    /**
     * Remove client nonce from cache.
     * @throws OAuthException
     */
    public function forget(string $signedServerToken): bool
    {
        $serverToken = $this->hmacService->extractAndVerify($signedServerToken);

        if (!$serverToken) {
            throw new OAuthException(OAuthStatusEnum::INVALID_TOKEN);
        }

        return $this->cache->forget(
            $this->getCacheKey($serverToken),
        );
    }
}
