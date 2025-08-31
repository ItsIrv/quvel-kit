<?php

namespace Modules\Phone\Services;

use Modules\Phone\Contracts\SmsDriverInterface;

/**
 * SMS service using configurable drivers.
 */
class SmsService
{
    public function __construct(
        private readonly SmsDriverInterface $driver,
    ) {
    }

    /**
     * Send an SMS message.
     */
    public function send(string $phone, string $message, ?string $from = null): void
    {
        $this->driver->send($phone, $message, $from);
    }
}
