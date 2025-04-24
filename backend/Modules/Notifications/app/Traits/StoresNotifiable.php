<?php

namespace Modules\Notifications\Traits;

use Illuminate\Database\Eloquent\Model;

/**
 * Trait StoresNotifiable
 *
 * Provides helper methods for storing and using the notifiable entity in notifications
 */
trait StoresNotifiable
{
    /**
     * The notifiable entity
     */
    protected $notifiable = null;

    /**
     * Store the notifiable entity for use in notification methods
     */
    public function storeNotifiable($notifiable): void
    {
        $this->notifiable = $notifiable;
    }

    /**
     * Get the stored notifiable entity
     */
    public function getNotifiable(): mixed
    {
        return $this->notifiable;
    }

    /**
     * Check if a notifiable entity is stored
     */
    public function hasNotifiable(): bool
    {
        return $this->notifiable !== null;
    }

    /**
     * Get the notifiable ID if available
     *
     * Prioritizes using public_id if available, falls back to primary key
     */
    public function getNotifiableId(): ?string
    {
        if (!$this->notifiable) {
            return null;
        }

        // First try to use public_id if it exists
        if (property_exists($this->notifiable, 'public_id') && $this->notifiable->public_id) {
            return (string) $this->notifiable->public_id;
        }

        if ($this->notifiable instanceof Model && $this->notifiable->getAttribute('public_id')) {
            return (string) $this->notifiable->getAttribute('public_id');
        }

        // Fall back to the primary key
        if (method_exists($this->notifiable, 'getKey')) {
            return (string) $this->notifiable->getKey();
        }

        return null;
    }

    /**
     * Append the notifiable ID to a channel name if available
     */
    public function appendNotifiableId(string $channelName): string
    {
        $notifiableId = $this->getNotifiableId();

        if ($notifiableId) {
            return "{$channelName}.{$notifiableId}";
        }

        return $channelName;
    }
}
