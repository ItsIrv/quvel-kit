<?php

namespace Modules\Auth\Logs\Actions\Fortify;

use Modules\Core\Logs\BaseLogger;
use Modules\Core\Logs\SanitizedContext;
use Illuminate\Log\LogManager;

/**
 * Logger for the password reset action.
 * Provides structured logging methods specific to the password reset process.
 */
class PasswordResetLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'password_reset';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful password reset.
     */
    public function passwordResetSuccess(int $userId, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('Password reset successful', [
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Log validation failure during password reset.
     */
    public function passwordResetValidationFailed(string $reason, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('Password reset validation failed', [
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => $reason,
        ]);
    }
}
