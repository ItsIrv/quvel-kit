<?php

namespace Modules\Tenant\Actions;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\UnauthorizedException;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\Transformers\TenantDumpTransformer;

/**
 * Action for frontend SSR to fetch all tenants.
 */
class TenantsDump
{
    private const CACHE_KEY = 'tenants';
    private const CACHE_TTL = 60 * 60; // 1 hour

    /**
     * Execute the action.
     *
     * @return AnonymousResourceCollection
     */
    public function __invoke(
        Request $request,
        TenantFindService $tenantFindService,
        CacheRepository $cache,
        TenantContext $tenantContext,
    ): AnonymousResourceCollection {
        dd($tenantContext->getConfigValue('appUrl'));
        if ($request->ip() !== '127.0.0.1') {
            // throw new UnauthorizedException();
        }

        if ($cache->has(self::CACHE_KEY)) {
            return TenantDumpTransformer::collection(
                $cache->get(self::CACHE_KEY),
            );
        }

        $tenants = $tenantFindService->findAll();
        $cache->put(self::CACHE_KEY, $tenants, self::CACHE_TTL);

        return TenantDumpTransformer::collection($tenants);
    }
}
