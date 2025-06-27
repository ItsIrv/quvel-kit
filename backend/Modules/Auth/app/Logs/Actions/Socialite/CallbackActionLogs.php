<?php

namespace Modules\Auth\Logs\Actions\Socialite;

use Modules\Core\Logs\BaseLogger;
use Modules\Core\Logs\SanitizedContext;
use Illuminate\Log\LogManager;

/**
 * Logger for the OAuth callback action.
 * Provides structured logging methods specific to the OAuth callback process.
 */
class CallbackActionLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'oauth_callback';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful OAuth callback authentication.
     */
    public function callbackSuccess(string $provider, ?int $userId, string $email, bool $isStateless, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('OAuth callback authentication successful', new SanitizedContext([
            'provider'     => $provider,
            'user_id'      => $userId,
            'email'        => $email,
            'is_stateless' => $isStateless,
            'ip_address'   => $ipAddress,
            'user_agent'   => $userAgent,
        ], [
            'email' => SanitizedContext::HASH,
        ]));
    }

    /**
     * Log a failed OAuth callback authentication.
     */
    public function callbackFailed(string $provider, string $reason, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('OAuth callback authentication failed', [
            'provider'   => $provider,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => $reason,
        ]);
    }

    /**
     * Log an OAuth callback error.
     */
    public function callbackError(string $provider, string $error, string $ipAddress, ?string $userAgent = null): void
    {
        $this->error('OAuth callback error', [
            'provider'   => $provider,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'error'      => $error,
        ]);
    }
}
