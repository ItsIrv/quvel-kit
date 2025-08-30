<?php

namespace Modules\Phone\Services;

use Illuminate\Support\Facades\Log;
use Modules\Phone\Contracts\SmsProviderInterface;

/**
 * SMS service using configurable providers.
 */
class SmsService
{
    public function __construct(
        private readonly SmsProviderInterface $provider,
    ) {
    }

    /**
     * Send an SMS message.
     */
    public function send(string $phone, string $message, ?string $from = null): void
    {
        $this->provider->send($phone, $message, $from);
    }
}
