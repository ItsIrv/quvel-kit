<?php

namespace Modules\Auth\Logs\Actions\Fortify;

use Modules\Core\Logs\BaseLogger;
use Modules\Core\Logs\SanitizedContext;
use Illuminate\Log\LogManager;

/**
 * Logger for the forgot password action.
 * Provides structured logging methods specific to the password reset request process.
 */
class ForgotPasswordLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'forgot_password';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful password reset request.
     */
    public function resetLinkSent(string $email, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('Password reset link sent', new SanitizedContext([
            'email'      => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ], [
            'email' => SanitizedContext::HASH,
        ]));
    }

    /**
     * Log a failed password reset request.
     */
    public function resetLinkFailed(string $email, string $reason, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('Password reset link failed', new SanitizedContext([
            'email'      => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => $reason,
        ], [
            'email' => SanitizedContext::HASH,
        ]));
    }
}
