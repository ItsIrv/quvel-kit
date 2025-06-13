<?php

namespace Modules\Core\Seeders;

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
     * @param array $baseConfig The base configuration to build upon
     * @return array The shared configuration values to seed
     */
    public function getSharedConfig(string $template, array $baseConfig): array
    {
        $pusherConfig = [];

        // Use seed parameters or environment variables
        if (isset($baseConfig['_seed_pusher_app_key'])) {
            $pusherConfig['pusher_app_key'] = $baseConfig['_seed_pusher_app_key'];
            $pusherConfig['pusher_app_secret'] = $baseConfig['_seed_pusher_app_secret'] ?? '';
            $pusherConfig['pusher_app_id'] = $baseConfig['_seed_pusher_app_id'] ?? '';
            $pusherConfig['pusher_app_cluster'] = $baseConfig['_seed_pusher_app_cluster'] ?? 'mt1';
        } elseif (env('PUSHER_APP_KEY')) {
            // Fallback to env for development
            $pusherConfig['pusher_app_key'] = env('PUSHER_APP_KEY');
            $pusherConfig['pusher_app_secret'] = env('PUSHER_APP_SECRET', '');
            $pusherConfig['pusher_app_id'] = env('PUSHER_APP_ID', '');
            $pusherConfig['pusher_app_cluster'] = env('PUSHER_APP_CLUSTER', 'mt1');
        }

        return $pusherConfig;
    }

    /**
     * Get visibility settings for the Pusher configuration.
     *
     * @return array Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array
    {
        return [
            'pusher_app_key' => 'public',
            'pusher_app_secret' => 'private',
            'pusher_app_id' => 'private',
            'pusher_app_cluster' => 'public',
        ];
    }

    /**
     * Get the priority for this shared seeder.
     *
     * @return int The priority level
     */
    public function getPriority(): int
    {
        return 15;
    }
}
