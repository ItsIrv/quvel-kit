<?php

namespace Modules\Tenant\Actions;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Tenant\Http\Resources\TenantDumpResource;
use Modules\Tenant\Services\TenantFindService;

/**
 * Action for frontend SSR to fetch all tenants and allow dynamic config on client.
 */
class TenantsDump
{
    private const string CACHE_KEY = 'tenants';

    private const int CACHE_TTL = 60; // 1 minute

    /**
     * Execute the action.
     */
    public function __invoke(
        TenantFindService $tenantFindService,
        CacheRepository $cache,
    ): AnonymousResourceCollection {
        // TODO: Decide how we want to internalize this.
        $tenants = [];

        if ($cache->has(self::CACHE_KEY)) {
            $tenants = $cache->get(self::CACHE_KEY);
        } else {
            $tenants = $tenantFindService->findAll();
            $cache->put(self::CACHE_KEY, $tenants, self::CACHE_TTL);
        }

        return TenantDumpResource::collection($tenants);
    }
}
