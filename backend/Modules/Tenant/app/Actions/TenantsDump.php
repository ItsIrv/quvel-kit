<?php

namespace Modules\Tenant\Actions;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Tenant\Http\Resources\TenantDumpResource;
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
    ): AnonymousResourceCollection {
        $tenants = [];

        if ($cache->has(self::CACHE_KEY)) {
            $tenants = $cache->get(self::CACHE_KEY);
        } else {
            $tenants = $tenantFindService->findAll();
            $cache->put(self::CACHE_KEY, $tenants, config('tenant.tenant_cache.cache_ttl'));
        }

        return TenantDumpResource::collection($tenants);
    }
}
