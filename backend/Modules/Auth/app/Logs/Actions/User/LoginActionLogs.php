<?php

namespace Modules\Auth\Logs\Actions\User;

use Modules\Core\Logs\BaseLogger;
use Illuminate\Log\LogManager;

/**
 * Logger for the login action.
 * Provides structured logging methods specific to the login process.
 */
class LoginActionLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'login';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful login attempt.
     */
    public function loginSuccess(string $email, int $userId, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('User login successful', [
            'email'      => $email,
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Log a failed login attempt due to invalid credentials.
     */
    public function loginFailedInvalidCredentials(string $email, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('Login failed: Invalid credentials', [
            'email'      => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => 'invalid_credentials',
        ]);
    }

    /**
     * Log a failed login attempt due to user not found.
     */
    public function loginFailedUserNotFound(string $email, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('Login failed: User not found', [
            'email'      => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => 'user_not_found',
        ]);
    }

    /**
     * Log a failed login attempt due to account being inactive.
     */
    public function loginFailedAccountInactive(
        string $email,
        int $userId,
        string $ipAddress,
        ?string $userAgent = null,
    ): void {
        $this->warning('Login failed: Account inactive', [
            'email'      => $email,
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => 'account_inactive',
        ]);
    }
}
