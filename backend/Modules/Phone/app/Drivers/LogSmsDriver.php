<?php

namespace Modules\Phone\Drivers;

use Illuminate\Support\Facades\Log;
use Modules\Phone\Contracts\SmsDriverInterface;

/**
 * Log SMS driver for development/testing.
 */
class LogSmsDriver implements SmsDriverInterface
{
    /**
     * Log the SMS message instead of sending.
     */
    public function send(string $to, string $message, ?string $from = null): void
    {
        Log::info('SMS Message (Log Driver)', [
            'provider'  => static::class,
            'to'        => $to,
            'from'      => $from ?? 'System',
            'message'   => $message,
            'timestamp' => now()->toISOString(),
        ]);
    }
}
