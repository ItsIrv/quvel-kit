<?php

namespace Modules\Phone\Contracts;

/**
 * Contract for SMS providers.
 */
interface SmsProviderInterface
{
    /**
     * Send an SMS message.
     *
     * @throws \Exception if sending fails
     */
    public function send(string $to, string $message, ?string $from = null): void;

}
