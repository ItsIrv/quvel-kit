<?php

namespace Modules\Auth\Logs\Actions\Fortify;

use Modules\Core\Logs\BaseLogger;
use Illuminate\Log\LogManager;

/**
 * Logger for the update user password action.
 * Provides structured logging methods specific to the password update process.
 */
class UpdateUserPasswordLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'update_password';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful password update.
     */
    public function passwordUpdateSuccess(int $userId, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('Password update successful', [
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Log validation failure during password update.
     */
    public function passwordUpdateValidationFailed(int $userId, string $reason, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('Password update validation failed', [
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => $reason,
        ]);
    }

    /**
     * Log failed password update due to incorrect current password.
     */
    public function passwordUpdateCurrentPasswordFailed(int $userId, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('Password update failed: Incorrect current password', [
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => 'incorrect_current_password',
        ]);
    }
}
