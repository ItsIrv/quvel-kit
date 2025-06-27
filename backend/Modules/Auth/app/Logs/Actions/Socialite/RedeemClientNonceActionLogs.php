<?php

namespace Modules\Auth\Logs\Actions\Socialite;

use Modules\Core\Logs\BaseLogger;
use Modules\Core\Logs\SanitizedContext;
use Illuminate\Log\LogManager;

/**
 * Logger for the redeem client nonce action.
 * Provides structured logging methods specific to the client nonce redemption process.
 */
class RedeemClientNonceActionLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'redeem_client_nonce';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful client nonce redemption.
     */
    public function nonceRedeemed(string $nonceId, int $userId, string $email, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('Client nonce redeemed successfully', new SanitizedContext([
            'nonce_id'   => $nonceId,
            'user_id'    => $userId,
            'email'      => $email,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ], [
            'email' => SanitizedContext::HASH,
        ]));
    }

    /**
     * Log a failed client nonce redemption.
     */
    public function nonceRedemptionFailed(string $nonceId, string $reason, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('Client nonce redemption failed', [
            'nonce_id'   => $nonceId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => $reason,
        ]);
    }

    /**
     * Log an invalid client nonce redemption attempt.
     */
    public function invalidNonceRedemption(string $nonceId, string $ipAddress, ?string $userAgent = null): void
    {
        $this->warning('Invalid client nonce redemption attempt', [
            'nonce_id'   => $nonceId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => 'invalid_nonce',
        ]);
    }
}