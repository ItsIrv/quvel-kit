<?php

namespace Modules\Auth\Logs\Actions\Socialite;

use Modules\Core\Logs\BaseLogger;
use Illuminate\Log\LogManager;

/**
 * Logger for the create client nonce action.
 * Provides structured logging methods specific to the client nonce creation process.
 */
class CreateClientNonceActionLogs extends BaseLogger
{
    /**
     * The log context prefix.
     */
    protected string $contextPrefix = 'create_client_nonce';

    /**
     * Create a new logger instance.
     */
    public function __construct(LogManager $logger)
    {
        parent::__construct($logger);
    }

    /**
     * Log a successful client nonce creation.
     */
    public function nonceCreated(string $nonceId, string $ipAddress, ?string $userAgent = null): void
    {
        $this->info('Client nonce created for stateless OAuth flow', [
            'nonce_id'   => $nonceId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Log a failed client nonce creation.
     */
    public function nonceCreationFailed(string $reason, string $ipAddress, ?string $userAgent = null): void
    {
        $this->error('Client nonce creation failed', [
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'reason'     => $reason,
        ]);
    }
}
