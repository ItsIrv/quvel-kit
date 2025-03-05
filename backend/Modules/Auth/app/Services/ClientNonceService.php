<?php

namespace Modules\Auth\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;

class ClientNonceService
{
    protected const CACHE_KEY_PREFIX = 'client_nonce_';

    public function __construct(
        private readonly CacheRepository $cache,
        private readonly ConfigRepository $config,
    ) {
    }

    /**
     * Validate and store client nonce.
     *
     * @param string $nonce Client nonce.
     * @throws OAuthException
     */
    public function validateNonce(string $nonce): string
    {
        $key = self::CACHE_KEY_PREFIX . $nonce;

        // If nonce already exists, reject it
        if ($this->cache->has($key)) {
            throw new OAuthException(
                OAuthStatusEnum::INVALID_NONCE,
            );
        }

        $ttl = $this->config->get(
            'auth.socialite.nonce_ttl',
            1,
        );

        // Store nonce temporarily
        $this->cache->put(
            $key,
            true,
            $ttl,
        );

        return $nonce;
    }

    public function assignUserToNonce(string $clientNonce, int $userId): void
    {
        $this->cache->put(
            self::CACHE_KEY_PREFIX . $clientNonce,
            $userId,
            $this->config->get(
                'auth.socialite.nonce_ttl',
                1,
            ),
        );
    }
}
