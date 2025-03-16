<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Modules\Auth\Services\NonceSessionService;

/*
 *--------------------------------------------------------------------------
 * Broadcast Channels
 *--------------------------------------------------------------------------
 *
 */

Broadcast::channel('auth.nonce.{nonce}', function ($user = null, string $nonce = ''): bool {
    \Log::info('WebSocket Auth Attempt', [
        'nonce_provided' => $nonce,
        'nonce_stored'   => app()->make(NonceSessionService::class)->getNonce(),
    ]);

    return app()->make(NonceSessionService::class)->getNonce() === $nonce;
});
