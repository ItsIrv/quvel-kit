<?php

namespace Modules\Auth\Logs\Actions\Fortify;

use Modules\Core\Logs\BaseLogger;
use Modules\Core\Logs\SanitizedContext;
use Illuminate\Log\LogManager;

/**
 * Logger for the email verification verify action.
 * Provides structured logging methods specific to the email verification process.
 */
class VerificationVerifyLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'verification_verify';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful email verification.
     */
    public function emailVerificationSuccess(int $userId, string $email, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('Email verification successful', new SanitizedContext([
            'user_id'    => $userId,
            'email'      => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ], [
            'email' => SanitizedContext::HASH,
        ]));
    }

    /**
     * Log a failed email verification.
     */
    public function emailVerificationFailed(int $userId, string $email, string $reason, string $ipAddress, ?string $userAgent = null): void
    {
        $this->error('Email verification failed', new SanitizedContext([
            'user_id'    => $userId,
            'email'      => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => $reason,
        ], [
            'email' => SanitizedContext::HASH,
        ]));
    }
}
