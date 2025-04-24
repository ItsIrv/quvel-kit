<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// User authentication channel
Broadcast::channel('App.Models.User.{publicId}', static function (User $user, string $publicId): bool {
    return $user->public_id === $publicId;
});
