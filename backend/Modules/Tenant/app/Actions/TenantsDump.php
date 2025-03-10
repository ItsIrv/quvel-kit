<?php

namespace Modules\Tenant\Actions;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\Transformers\TenantDumpTransformer;

/**
 * Action for frontend SSR to fetch all tenants and allow dynamic config on client.
 */
class TenantsDump
{
    private const CACHE_KEY = 'tenants';
    private const CACHE_TTL = 60; // 1 minute

    /**
     * Execute the action.
     *
     * @return AnonymousResourceCollection
     */
    public function __invoke(
        Request $request,
        TenantFindService $tenantFindService,
        CacheRepository $cache,
    ): AnonymousResourceCollection {
        // TODO: Decide how we want to internalize this.
        if ($request->ip() !== '127.0.0.1') {
            // throw new UnauthorizedException();
        }

        $tenants = [];

        if ($cache->has(self::CACHE_KEY) && !app()->isLocal()) {
            $tenants = $cache->get(self::CACHE_KEY);
        } else {
            $tenants = $tenantFindService->findAll();
            $cache->put(self::CACHE_KEY, $tenants, self::CACHE_TTL);
        }

        return TenantDumpTransformer::collection($tenants);
    }
}
