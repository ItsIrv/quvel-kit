<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class TestNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via(): array
    {
        return ['broadcast'];
    }

    public function toBroadcast(): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => 'Test Notification',
        ]);
    }
}
