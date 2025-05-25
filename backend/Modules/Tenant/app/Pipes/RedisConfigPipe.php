<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Support\Facades\Redis;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Handles Redis configuration for tenants.
 * Octane-safe: No static state needed.
 */
class RedisConfigPipe implements ConfigurationPipeInterface
{
    /**
     * Apply Redis configuration.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Redis configuration
        $hasRedisChanges = false;

        if (isset($tenantConfig['redis_client'])) {
            $config->set('database.redis.client', $tenantConfig['redis_client']);
            $hasRedisChanges = true;
        }

        if (isset($tenantConfig['redis_host'])) {
            $config->set('database.redis.default.host', $tenantConfig['redis_host']);
            $hasRedisChanges = true;
        }

        if (isset($tenantConfig['redis_password'])) {
            $config->set('database.redis.default.password', $tenantConfig['redis_password']);
            $hasRedisChanges = true;
        }

        if (isset($tenantConfig['redis_port'])) {
            $config->set('database.redis.default.port', $tenantConfig['redis_port']);
            $hasRedisChanges = true;
        }

        // Add tenant-specific prefix to Redis keys for isolation
        if (!isset($tenantConfig['redis_prefix'])) {
            $config->set('database.redis.default.prefix', "tenant_{$tenant->id}:");
            $config->set('database.redis.cache.prefix', "tenant_{$tenant->id}:");
            $hasRedisChanges = true;
        } else {
            $config->set('database.redis.default.prefix', $tenantConfig['redis_prefix']);
            $config->set('database.redis.cache.prefix', $tenantConfig['redis_prefix']);
            $hasRedisChanges = true;
        }

        // Apply the changes to the actual resources
        if ($hasRedisChanges) {
            $this->refreshRedisConnections();
        }

        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    protected function refreshRedisConnections(): void
    {
        try {
            if (app()->bound(RedisFactory::class)) {
                Redis::flushConnections();
            }

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Refreshed Redis connections with new configuration");
            }
        } catch (\Exception $e) {
            logger()->error("[Tenant] Failed to refresh Redis connections: {$e->getMessage()}");
        }
    }

    /**
     * Reset Redis connections.
     * Octane-safe: No static state to clean up.
     */
    public static function resetResources(): void
    {
        try {
            if (app()->bound(RedisFactory::class)) {
                Redis::flushConnections();
            }

            if (app()->environment(['local', 'development', 'testing'])) {
                logger()->debug("[Tenant] Reset Redis connections with current configuration");
            }
        } catch (\Exception $e) {
            logger()->error("[Tenant] Failed to reset Redis connections: {$e->getMessage()}");
        }
    }

    public function handles(): array
    {
        return ['redis_client', 'redis_host', 'redis_password', 'redis_port', 'redis_prefix'];
    }

    public function priority(): int
    {
        return 84;
    }
}
