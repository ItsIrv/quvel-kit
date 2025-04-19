<?php

namespace Modules\Tenant\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Modules\Tenant\Traits\GetsTenant;

class TenantNotification extends Notification
{
    use GetsTenant;

    private readonly string $message;
    private ?string $suffix = null;
    private ?string $privacy = null;
    private ?string $tenantPublicId = null;

    public function __construct(
        string $message = 'Test Notification',
        ?string $suffix = null,
        ?string $privacy = null,
        ?string $tenantPublicId = null,
    ) {
        $this->message        = $message;
        $this->suffix         = $suffix;
        $this->privacy        = $privacy;
        $this->tenantPublicId ??= $tenantPublicId ?? $this->getTenantPublicId();
    }

    public function via(): array
    {
        return ['broadcast'];
    }

    public function toBroadcast($notifiable = null): BroadcastMessage
    {
        return new BroadcastMessage([
            'message' => $this->message,
        ]);
    }

    public function broadcastOn(): Channel
    {
        $name = "tenant.{$this->tenantPublicId}" . ($this->suffix ? ".{$this->suffix}" : '');

        return match ($this->privacy) {
            'private'  => new PrivateChannel($name),
            'presence' => new PresenceChannel($name),
            default    => new Channel($name),
        };
    }
}
