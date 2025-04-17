<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via(): array
    {
        return ['broadcast', 'database'];
    }

    public function toDatabase(): array
    {
        return [
            'message' => 'Test Notification',
        ];
    }

    public function toBroadcast(): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => 'Test Notification',
        ]);
    }
}
