<?php

namespace Modules\Tenant\Seeders\Shared;

use Modules\Tenant\Contracts\TenantSharedSeederInterface;

/**
 * Shared seeder for Pusher configuration.
 *
 * Provides Pusher/WebSocket configuration that applies to all tenant templates.
 */
class PusherSharedSeeder implements TenantSharedSeederInterface
{
    /**
     * Generate shared Pusher configuration for all templates.
     *
     * @param string $template The tenant template for context
     * @param array<string, mixed> $baseConfig The base configuration to build upon
     * @return array<string, mixed> The shared configuration values to seed
     */
    public function getSharedConfig(string $template, array $baseConfig): array
    {
        $pusherConfig = [];

        // Use direct parameters or environment variables for fallback
        if (isset($baseConfig['pusher_app_key'])) {
            $pusherConfig['pusher_app_key']     = $baseConfig['pusher_app_key'];
            $pusherConfig['pusher_app_secret']  = $baseConfig['pusher_app_secret'] ?? '';
            $pusherConfig['pusher_app_id']      = $baseConfig['pusher_app_id'] ?? '';
            $pusherConfig['pusher_app_cluster'] = $baseConfig['pusher_app_cluster'] ?? 'mt1';
        } elseif (config('broadcasting.connections.pusher.key') !== null) {
            // Fallback to config for development
            $pusherConfig['pusher_app_key']     = config('broadcasting.connections.pusher.key');
            $pusherConfig['pusher_app_secret']  = config('broadcasting.connections.pusher.secret', '');
            $pusherConfig['pusher_app_id']      = config('broadcasting.connections.pusher.app_id', '');
            $pusherConfig['pusher_app_cluster'] = config('broadcasting.connections.pusher.options.cluster', 'mt1');
        }

        return $pusherConfig;
    }

    /**
     * Get visibility settings for the Pusher configuration.
     *
     * @return array<string, mixed> Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array
    {
        return [
            'pusher_app_key'     => 'public',
            'pusher_app_secret'  => 'private',
            'pusher_app_id'      => 'private',
            'pusher_app_cluster' => 'public',
        ];
    }
}
