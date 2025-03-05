<?php

namespace Modules\Auth\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class ServerTokenService
{
    protected const CACHE_KEY_PREFIX = 'server_token_';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
    ) {
    }

    /**
     * Generate a secure server token and map it to a client nonce.
     */
    public function generateServerToken(string $clientNonce): string
    {
        $serverToken = bin2hex(random_bytes(32));
        $ttl         = $this->config->get(
            'auth.socialite.token_ttl',
            1,
        );

        $this->cache->put(
            self::CACHE_KEY_PREFIX . $serverToken,
            $clientNonce,
            $ttl,
        );

        return $serverToken;
    }

    /**
     * Retrieve client nonce from server token.
     */
    public function getClientNonce(string $serverToken): ?string
    {
        return $this->cache->get(
            self::CACHE_KEY_PREFIX . $serverToken
        );
    }

    /**
     * Remove client nonce from cache.
     */
    public function forgetClientNonce(string $serverToken): bool
    {
        return $this->cache->forget(
            self::CACHE_KEY_PREFIX . $serverToken,
        );
    }
}
