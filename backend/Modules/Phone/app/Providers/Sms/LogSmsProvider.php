<?php

namespace Modules\Phone\Providers\Sms;

use Illuminate\Support\Facades\Log;
use Modules\Phone\Contracts\SmsProviderInterface;

/**
 * Log SMS provider for development/testing.
 */
class LogSmsProvider implements SmsProviderInterface
{
    /**
     * Log the SMS message instead of sending.
     */
    public function send(string $to, string $message, ?string $from = null): void
    {
        Log::info('SMS Message (Log Provider)', [
            'provider'  => static::class,
            'to'        => $to,
            'from'      => $from ?? 'System',
            'message'   => $message,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
