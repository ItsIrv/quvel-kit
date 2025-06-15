<?php

namespace Modules\Notifications\Notifications;

use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification
{
    public function __construct(
        private readonly string $message = 'Test Notification',
    ) {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(): array
    {
        return ['broadcast', 'database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(): array
    {
        return [
            'message' => $this->message,
        ];
    }

    public function toBroadcast(mixed $notifiable = null): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => $this->message,
        ]);
    }
}
