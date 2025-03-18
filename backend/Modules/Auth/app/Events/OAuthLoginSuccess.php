<?php

namespace Modules\Auth\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

// TODO: Need to pick a random channel name instead of the full nonce.
class OAuthLoginSuccess implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(public string $signedNonce) {}

    /**
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        return [new Channel("auth.nonce.$this->signedNonce")];
    }

    /**
     * @return array<string, bool>
     */
    public function broadcastWith(): array
    {
        return ['success' => true];
    }

    /**
     * Explicit event name for Laravel Echo
     */
    public function broadcastAs(): string
    {
        return 'oauth.success';
    }
}
