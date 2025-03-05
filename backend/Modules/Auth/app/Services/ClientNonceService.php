<?php

namespace Modules\Auth\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Auth\Enums\SocialiteStatusEnum;
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
                __(SocialiteStatusEnum::INVALID_NONCE->value),
            );
        }

        $ttl = $this->config->get('auth.socialite.nonce_ttl', 300);

        // Store nonce temporarily
        $this->cache->put(
            $key,
            true,
            $ttl,
        );

        return $nonce;
    }
}
