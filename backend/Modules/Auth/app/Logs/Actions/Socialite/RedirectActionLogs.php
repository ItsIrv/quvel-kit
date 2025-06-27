<?php

namespace Modules\Auth\Logs\Actions\Socialite;

use Modules\Core\Logs\BaseLogger;
use Illuminate\Log\LogManager;

/**
 * Logger for the OAuth redirect action.
 * Provides structured logging methods specific to the OAuth redirect process.
 */
class RedirectActionLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'oauth_redirect';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful OAuth redirect.
     */
    public function redirectSuccess(string $provider, ?string $nonce, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('OAuth redirect initiated', [
            'provider'   => $provider,
            'has_nonce'  => $nonce !== null,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Log a failed OAuth redirect.
     */
    public function redirectFailed(string $provider, string $reason, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('OAuth redirect failed', [
            'provider'   => $provider,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => $reason,
        ]);
    }

    /**
     * Log an OAuth redirect error.
     */
    public function redirectError(string $provider, string $error, string $ipAddress, ?string $userAgent = null): void
    {
        $this->error('OAuth redirect error', [
            'provider'   => $provider,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'error'      => $error,
        ]);
    }
}