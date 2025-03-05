<?php

namespace Modules\Auth\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Str;

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
        $serverToken = Str::random(64);
        $ttl         = $this->config->get('auth.socialite.token_ttl', 300);

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
}
