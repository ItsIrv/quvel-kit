<?php

namespace Modules\Auth\Services;

use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;

class NonceSessionService
{
    private const string SESSION_KEY   = 'auth.nonce';
    private const string TIMESTAMP_KEY = 'auth.nonce.timestamp';

    public function __construct(
        private readonly Session $session,
        private readonly ConfigRepository $config,
    ) {
    }

    /**
     * Store a nonce in the session with a timestamp.
     */
    public function setNonce(string $nonce): void
    {
        $this->session->put(self::SESSION_KEY, $nonce);
        $this->session->put(self::TIMESTAMP_KEY, Carbon::now());
    }

    /**
     * Get the nonce if it exists and is not expired.
     */
    public function getNonce(): ?string
    {
        if (!$this->isValid()) {
            $this->clear();

            return null;
        }

        return $this->session->get(self::SESSION_KEY);
    }

    /**
     * Check if the nonce is still valid based on configured TTL.
     */
    public function isValid(): bool
    {
        $nonce     = $this->session->get(self::SESSION_KEY);
        $timestamp = $this->session->get(self::TIMESTAMP_KEY);

        if (!isset($nonce, $timestamp)) {
            return false;
        }

        $ttl       = $this->config->get('auth.oauth.nonce_ttl', 1);
        $expiresAt = Carbon::parse($timestamp)->addSeconds($ttl);

        return Carbon::now()->lessThan($expiresAt);
    }

    /**
     * Clear all nonce-related data from the session.
     */
    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
        $this->session->forget(self::TIMESTAMP_KEY);
    }
}
