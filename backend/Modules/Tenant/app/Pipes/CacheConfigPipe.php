<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Logs\Pipes\CacheConfigPipeLogs;
use Modules\Tenant\Models\Tenant;

/**
 * Handles cache configuration for tenants.
 */
class CacheConfigPipe implements ConfigurationPipeInterface
{
    /**
     * Apply cache configuration.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Check if tenant has dedicated cache feature (only if tiers are enabled)
        if (config('tenant.enable_tiers', false) && !$tenant->hasFeature('dedicated_cache')) {
            // Basic tier: use shared cache with tenant prefix only
            $config->set('cache.prefix', "tenant_{$tenant->id}_");

            // Pass to next pipe
            return $next([
                'tenant'       => $tenant,
                'config'       => $config,
                'tenantConfig' => $tenantConfig,
            ]);
        }

        // Standard tier and above: can have dedicated cache configuration
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
            $config->set('cache.prefix', "tenant_{$tenant->id}_");
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

    public function handles(): array
    {
        return ['cache_store', 'cache_prefix'];
    }

    public function priority(): int
    {
        return 85;
    }
}
