<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Logs\Pipes\CacheConfigPipeLogs;
use Modules\Tenant\Models\Tenant;

/**
 * Handles cache configuration for tenants.
 */
class CacheConfigPipe extends BaseConfigurationPipe
{
    /**
     * Apply cache configuration to Laravel config repository.
     *
     * @param Tenant $tenant The tenant context
     * @param ConfigRepository $config Laravel config repository
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed Result of calling $next()
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        if ($this->hasValue($tenantConfig, 'cache_store')) {
            $config->set('cache.default', $tenantConfig['cache_store']);
        }

        // Always set a tenant-specific cache prefix
        if ($this->hasValue($tenantConfig, 'cache_prefix')) {
            $config->set('cache.prefix', $tenantConfig['cache_prefix']);
        } else {
            // Default to tenant-specific prefix for isolation
            $config->set('cache.prefix', "tenant_{$tenant->public_id}_");
        }

        // Apply the changes to the actual resources
        $this->rebindCacheManager();

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
     * Resolve cache configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @return array<string, mixed> Empty array - cache configuration is internal only
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        return [];
    }
}
