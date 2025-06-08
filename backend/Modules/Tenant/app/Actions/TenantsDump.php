<?php

namespace Modules\Tenant\Actions;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Tenant\Http\Resources\TenantCacheResource;
use Modules\Tenant\Services\FindService;

/**
 * Action for frontend SSR to fetch all tenants and allow dynamic config on client.
 */
class TenantsDump
{
    private const string CACHE_KEY = 'tenants';

    /**
     * Execute the action.
     */
    public function __invoke(
        FindService $tenantFindService,
        CacheRepository $cache,
        ConfigRepository $config,
        Application $app,
    ): AnonymousResourceCollection {
        $tenants = [];

        $tenants = $app->environment('local')
            ? $tenantFindService->findAll()
            : $cache->remember(
                self::CACHE_KEY,
                $config->get('tenant.tenant_cache.cache_ttl'),
                fn (): Collection => $tenantFindService->findAll()
            );

        return TenantCacheResource::collection($tenants);
    }
}
