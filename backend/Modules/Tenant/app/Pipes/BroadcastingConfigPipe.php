<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;

/**
 * Handles broadcasting configuration for tenants.
 */
class BroadcastingConfigPipe extends BaseConfigurationPipe
{
    /**
     * Apply broadcasting configuration to Laravel config repository.
     *
     * @param Tenant $tenant The tenant context
     * @param ConfigRepository $config Laravel config repository
     * @param array $tenantConfig The tenant configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed Result of calling $next()
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Apply default broadcaster
        if (isset($tenantConfig['broadcast_driver'])) {
            $config->set('broadcasting.default', $tenantConfig['broadcast_driver']);
        }

        // Configure Pusher for tenant
        if (isset($tenantConfig['pusher_app_id'])) {
            $config->set('broadcasting.connections.pusher.app_id', $tenantConfig['pusher_app_id']);
        }

        if (isset($tenantConfig['pusher_app_key'])) {
            $config->set('broadcasting.connections.pusher.key', $tenantConfig['pusher_app_key']);
            $config->set('broadcasting.connections.pusher.options.key', $tenantConfig['pusher_app_key']);
        }

        if (isset($tenantConfig['pusher_app_secret'])) {
            $config->set('broadcasting.connections.pusher.secret', $tenantConfig['pusher_app_secret']);
        }

        if (isset($tenantConfig['pusher_app_cluster'])) {
            $config->set('broadcasting.connections.pusher.options.cluster', $tenantConfig['pusher_app_cluster']);
        }

        // Configure tenant-specific Pusher options
        if (isset($tenantConfig['pusher_scheme'])) {
            $config->set('broadcasting.connections.pusher.options.scheme', $tenantConfig['pusher_scheme']);
        }

        if (isset($tenantConfig['pusher_host'])) {
            $config->set('broadcasting.connections.pusher.options.host', $tenantConfig['pusher_host']);
        }

        if (isset($tenantConfig['pusher_port'])) {
            $config->set('broadcasting.connections.pusher.options.port', $tenantConfig['pusher_port']);
        }

        // Configure Reverb (Laravel's WebSocket server) for tenant
        if (isset($tenantConfig['reverb_app_id'])) {
            $config->set('broadcasting.connections.reverb.app_id', $tenantConfig['reverb_app_id']);
        }

        if (isset($tenantConfig['reverb_app_key'])) {
            $config->set('broadcasting.connections.reverb.key', $tenantConfig['reverb_app_key']);
            $config->set('broadcasting.connections.reverb.options.key', $tenantConfig['reverb_app_key']);
        }

        if (isset($tenantConfig['reverb_app_secret'])) {
            $config->set('broadcasting.connections.reverb.secret', $tenantConfig['reverb_app_secret']);
        }

        if (isset($tenantConfig['reverb_host'])) {
            $config->set('broadcasting.connections.reverb.options.host', $tenantConfig['reverb_host']);
        }

        if (isset($tenantConfig['reverb_port'])) {
            $config->set('broadcasting.connections.reverb.options.port', $tenantConfig['reverb_port']);
        }

        // Configure Redis broadcasting with tenant prefix
        if ($config->get('broadcasting.default') === 'redis' || isset($tenantConfig['redis_broadcast_prefix'])) {
            $prefix = $tenantConfig['redis_broadcast_prefix'] ?? 'tenant_' . $tenant->public_id;
            $config->set('broadcasting.connections.redis.prefix', $prefix);
        }

        // Configure Ably for enterprise tenants
        if (isset($tenantConfig['ably_key'])) {
            $config->set('broadcasting.connections.ably.key', $tenantConfig['ably_key']);
        }

        // Pass to next pipe
        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    /**
     * Resolve broadcasting configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array $tenantConfig The tenant configuration array
     * @return array ['values' => array, 'visibility' => array] Resolved values and visibility
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        $values = [];
        $visibility = [];

        if ($this->hasValue($tenantConfig, 'pusher_app_key')) {
            $values['pusherAppKey'] = $tenantConfig['pusher_app_key'];
            $visibility['pusherAppKey'] = 'public';
        }

        if ($this->hasValue($tenantConfig, 'pusher_app_cluster')) {
            $values['pusherAppCluster'] = $tenantConfig['pusher_app_cluster'];
            $visibility['pusherAppCluster'] = 'public';
        }

        return ['values' => $values, 'visibility' => $visibility];
    }

    /**
     * Get the configuration keys that this pipe handles.
     *
     * @return array<string> Array of configuration keys
     */
    public function handles(): array
    {
        return [
            'broadcast_driver',
            'pusher_app_id',
            'pusher_app_key',
            'pusher_app_secret',
            'pusher_app_cluster',
            'pusher_scheme',
            'pusher_host',
            'pusher_port',
            'reverb_app_id',
            'reverb_app_key',
            'reverb_app_secret',
            'reverb_host',
            'reverb_port',
            'redis_broadcast_prefix',
            'ably_key',
        ];
    }

    /**
     * Get the priority for this pipe (higher = runs first).
     *
     * @return int Priority value
     */
    public function priority(): int
    {
        return 45;
    }
}
