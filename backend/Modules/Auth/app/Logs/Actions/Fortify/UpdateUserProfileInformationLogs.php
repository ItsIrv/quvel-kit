<?php

namespace Modules\Auth\Logs\Actions\Fortify;

use Modules\Core\Logs\BaseLogger;
use Modules\Core\Logs\SanitizedContext;
use Illuminate\Log\LogManager;

/**
 * Logger for the update user profile information action.
 * Provides structured logging methods specific to the profile update process.
 */
class UpdateUserProfileInformationLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'update_profile';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful profile update.
     */
    public function profileUpdateSuccess(int $userId, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('Profile update successful', [
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Log a successful profile update with email change requiring verification.
     */
    public function profileUpdateWithEmailChange(int $userId, string $oldEmail, string $newEmail, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('Profile update with email change - verification required', new SanitizedContext([
            'user_id'    => $userId,
            'old_email'  => $oldEmail,
            'new_email'  => $newEmail,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ], [
            'old_email' => SanitizedContext::HASH,
            'new_email' => SanitizedContext::HASH,
        ]));
    }

    /**
     * Log validation failure during profile update.
     */
    public function profileUpdateValidationFailed(int $userId, string $reason, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('Profile update validation failed', [
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => $reason,
        ]);
    }
}
