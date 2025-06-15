<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Modules\Tenant\Logs\Pipes\RedisConfigPipeLogs;
use Modules\Tenant\Models\Tenant;

/**
 * Handles Redis configuration for tenants.
 */
class RedisConfigPipe extends BaseConfigurationPipe
{
    /**
     * Apply Redis configuration to Laravel config repository.
     *
     * @param Tenant $tenant The tenant context
     * @param ConfigRepository $config Laravel config repository
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed Result of calling $next()
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Skip Redis configuration if Redis is not available
        if (!$this->isRedisAvailable()) {
            return $next([
                'tenant'       => $tenant,
                'config'       => $config,
                'tenantConfig' => $tenantConfig,
            ]);
        }

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
            $config->set('database.redis.default.prefix', "tenant_{$tenant->public_id}:");
            $config->set('database.redis.cache.prefix', "tenant_{$tenant->public_id}:");
            $hasRedisChanges = true;
        } else {
            $config->set('database.redis.default.prefix', $tenantConfig['redis_prefix']);
            $config->set('database.redis.cache.prefix', $tenantConfig['redis_prefix']);
            $hasRedisChanges = true;
        }

        // Apply the changes to the actual resources
        $this->refreshRedisConnections();

        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    /**
     * Resolve Redis configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @return array<string, mixed> Empty array - Redis configuration is internal only
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        return [];
    }

    /**
     * Check if Redis is available in the application.
     *
     * @return bool True if Redis is available
     */
    protected function isRedisAvailable(): bool
    {
        return app()->bound(RedisFactory::class) &&
            extension_loaded('redis') &&
            class_exists(\Illuminate\Support\Facades\Redis::class);
    }

    protected function refreshRedisConnections(): void
    {
        try {
            if (!$this->isRedisAvailable()) {
                return;
            }

            // Rebind the Redis factory to force new connections with updated config
            app()->extend(RedisFactory::class, function ($redisFactory, $app) {
                return new \Illuminate\Redis\RedisManager(
                    $app,
                    $app['config']['database.redis.client'] ?? 'phpredis',
                    $app['config']['database.redis'] ?? []
                );
            });

            // Clear the resolved instance
            app()->forgetInstance(RedisFactory::class);
            app()->forgetInstance('redis');

            if (app()->environment(['local', 'development', 'testing']) && app()->bound(RedisConfigPipeLogs::class)) {
                app(RedisConfigPipeLogs::class)->connectionsRefreshed();
            }
        } catch (\Exception $e) {
            if (app()->bound(RedisConfigPipeLogs::class)) {
                app(RedisConfigPipeLogs::class)->connectionsFailed($e->getMessage());
            }
        }
    }

}
