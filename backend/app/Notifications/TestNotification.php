<?php

namespace App\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class TestNotification extends Notification implements ShouldQueue
{
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

    public function toBroadcast($notifiable = null): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => 'Test Notification',
        ]);
    }
}
