<?php

namespace Modules\Auth\Logs\Actions\User;

use Modules\Core\Logs\BaseLogger;
use Modules\Core\Logs\SanitizedContext;
use Illuminate\Log\LogManager;

/**
 * Logger for the register action.
 * Provides structured logging methods specific to the registration process.
 */
class RegisterActionLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'register';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful registration.
     */
    public function registerSuccess(string $email, int $userId, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('User registration successful', new SanitizedContext([
            'email'      => $email,
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ], [
            'email' => SanitizedContext::HASH,
        ]));
    }

    /**
     * Log a successful registration with auto-login.
     */
    public function registerSuccessWithLogin(string $email, int $userId, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('User registration successful with auto-login', new SanitizedContext([
            'email'      => $email,
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ], [
            'email' => SanitizedContext::HASH,
        ]));
    }

    /**
     * Log a failed registration due to email already in use.
     */
    public function registerFailedEmailInUse(string $email, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('Registration failed: Email already in use', new SanitizedContext([
            'email'      => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => 'email_already_in_use',
        ], [
            'email' => SanitizedContext::HASH,
        ]));
    }
}
