<?php

namespace Modules\Auth\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\DTO\OAuthCallbackResult;

// TODO: Need to pick a random channel name instead of the full nonce.
class OAuthLoginResult implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        public readonly string $signedNonce,
        private readonly OAuthCallbackResult $result
    ) {}

    /**
     * @return Channel[]
     */
    public function broadcastOn(): array
    {
        return [new Channel("auth.nonce.$this->signedNonce")];
    }

    /**
     * @return array<string, string>
     */
    public function broadcastWith(): array
    {
        return ['status' => $this->result->getStatus()->value];
    }

    /**
     * Explicit event name for Laravel Echo
     */
    public function broadcastAs(): string
    {
        return 'oauth.result';
    }
}
