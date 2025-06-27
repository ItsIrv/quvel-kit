<?php

namespace Modules\Auth\Logs\Actions\User;

use Modules\Core\Logs\BaseLogger;
use Illuminate\Log\LogManager;

/**
 * Logger for the logout action.
 * Provides structured logging methods specific to the logout process.
 */
class LogoutActionLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'logout';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful logout.
     */
    public function logoutSuccess(?int $userId = null, string $ipAddress = 'unknown', ?string $userAgent = null): void
    {
        $this->info('User logout successful', [
            'user_id'    => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
