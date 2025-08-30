<?php

namespace Modules\Phone\Providers\Sms;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Modules\Phone\Contracts\SmsProviderInterface;

/**
 * ClickSend SMS provider implementation.
 */
class ClickSendSmsProvider implements SmsProviderInterface
{
    private string $username;
    private string $apiKey;
    private string $defaultFrom;

    public function __construct()
    {
        $this->username    = config('phone.sms.providers.clicksend.username', '');
        $this->apiKey      = config('phone.sms.providers.clicksend.api_key', '');
        $this->defaultFrom = config('phone.sms.providers.clicksend.from', 'App');
    }

    /**
     * Send an SMS message via ClickSend.
     */
    public function send(string $to, string $message, ?string $from = null): void
    {
        if (empty($this->username) || empty($this->apiKey)) {
            throw new Exception('ClickSend credentials not configured');
        }

        try {
            $response = Http::withBasicAuth($this->username, $this->apiKey)
                ->timeout(10)
                ->post('https://rest.clicksend.com/v3/sms/send', [
                    'messages' => [
                        [
                            'to'   => $to,
                            'body' => $message,
                            'from' => $from ?? $this->defaultFrom,
                        ],
                    ],
                ]);

            if ($response->successful()) {
                Log::info('SMS sent via ClickSend', [
                    'provider' => static::class,
                    'to'       => $to,
                    'status'   => $response->status(),
                ]);
                return;
            }

            Log::error('ClickSend SMS failed', [
                'to'       => $to,
                'status'   => $response->status(),
                'response' => $response->body(),
            ]);

            throw new Exception("ClickSend API error: {$response->status()}");
        } catch (Exception $e) {
            Log::error('ClickSend SMS exception', [
                'to'    => $to,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
