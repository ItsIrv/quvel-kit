<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Pipes\BaseConfigurationPipe;
use Modules\Tenant\Logs\Pipes\CacheConfigPipeLogs;
use Modules\Tenant\Models\Tenant;

/**
 * Handles cache configuration for tenants.
 */
class CacheConfigPipe extends BaseConfigurationPipe
{
    /**
     * Apply cache configuration.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Apply cache configuration
        $hasCacheChanges = false;

        if (isset($tenantConfig['cache_store'])) {
            $config->set('cache.default', $tenantConfig['cache_store']);
            $hasCacheChanges = true;
        }

        // Always set a tenant-specific cache prefix
        if (isset($tenantConfig['cache_prefix'])) {
            $config->set('cache.prefix', $tenantConfig['cache_prefix']);
            $hasCacheChanges = true;
        } else {
            // Default to tenant-specific prefix for isolation
            $config->set('cache.prefix', "tenant_{$tenant->public_id}_");
            $hasCacheChanges = true;
        }

        // Apply the changes to the actual resources
        if ($hasCacheChanges) {
            $this->rebindCacheManager();
        }

        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    protected function rebindCacheManager(): void
    {
        try {
            app()->extend(CacheManager::class, function ($cacheManager, $app): CacheManager {
                return new CacheManager($app);
            });

            app()->forgetInstance(CacheManager::class);
            app()->forgetInstance(CacheRepository::class);

            if (app()->environment(['local', 'development', 'testing']) && app()->bound(CacheConfigPipeLogs::class)) {
                app(CacheConfigPipeLogs::class)->cacheManagerRebound();
            }
        } catch (\Exception $e) {
            if (app()->bound(CacheConfigPipeLogs::class)) {
                app(CacheConfigPipeLogs::class)->rebindFailed($e->getMessage());
            }
        }
    }

    /**
     * Resolve cache configuration values without side effects.
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        $resolved = [];

        // Include all cache config values that are explicitly set
        foreach ($this->handles() as $key) {
            if (isset($tenantConfig[$key])) {
                $resolved[$key] = $tenantConfig[$key];
            }
        }

        return $resolved;
    }

    public function handles(): array
    {
        return ['cache_store', 'cache_prefix'];
    }

    public function priority(): int
    {
        return 85;
    }
}
