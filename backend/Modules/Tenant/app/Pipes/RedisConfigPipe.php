<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Logs\Pipes\RedisConfigPipeLogs;
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

    /**
     * Check if Redis is available in the application.
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

    /**
     * Reset Redis connections.
     * Octane-safe: No static state to clean up.
     */
    public static function resetResources(): void
    {
        try {
            $instance = new static();

            if (!$instance->isRedisAvailable()) {
                return;
            }

            // Rebind Redis factory with current config
            app()->extend(RedisFactory::class, function ($redisFactory, $app) {
                return new \Illuminate\Redis\RedisManager(
                    $app,
                    $app['config']['database.redis.client'] ?? 'phpredis',
                    $app['config']['database.redis'] ?? []
                );
            });

            // Clear the resolved instances
            app()->forgetInstance(RedisFactory::class);
            app()->forgetInstance('redis');

            if (app()->environment(['local', 'development', 'testing']) && app()->bound(RedisConfigPipeLogs::class)) {
                app(RedisConfigPipeLogs::class)->connectionsReset();
            }
        } catch (\Exception $e) {
            if (app()->bound(RedisConfigPipeLogs::class)) {
                app(RedisConfigPipeLogs::class)->resetFailed($e->getMessage());
            }
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
