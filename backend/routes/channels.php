<?php

use Illuminate\Support\Facades\Broadcast;

// User authentication channel
Broadcast::channel('App.Models.User.{id}', static function ($user, $id): bool {
    return (int) $user->id === (int) $id;
});

// Tenant private channel - always return true for testing
Broadcast::channel('tenant.{tenantId}.users', function ($user, $tenantId) {
    // For testing purposes, always authorize
    return true;
});

// Tenant presence channel - always return true with user data for testing
Broadcast::channel('presence-tenant.{tenantId}.chat', function ($user, $tenantId) {
    // For testing purposes, always authorize with some user data
    return [
        'id'    => $user->id ?? 1,
        'name'  => $user->name ?? 'Test User',
        'email' => $user->email ?? 'test@example.com',
    ];
});

// Allow all other channels for testing
Broadcast::channel('*', function () {
    return true;
});
