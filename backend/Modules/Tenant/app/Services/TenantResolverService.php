<?php

namespace Modules\Tenant\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;

/**
 * Service to resolve tenants.
 */
class TenantResolverService
{
    /**
     * Create a new TenantResolverService instance.
     *
     * @param TenantFindService $tenantFindService
     * @param TenantSessionService $tenantSessionService
     */
    public function __construct(
        private TenantFindService    $tenantFindService,
        private TenantSessionService $tenantSessionService,
    ) {
    }

    /**
     * Resolve the tenant by checking the session first, then the database.
     * If no tenant is found, throws an exception.
     *
     * @param Request $request
     * @return Tenant
     * @throws TenantNotFoundException
     */
    public function resolveTenant(Request $request): Tenant
    {
        $tenant = $this->tenantSessionService->getTenant();

        if ($tenant) {
            return $tenant;
        }

        // TODO: Add a check for Cache.
        $domain = $request->getHost();
        $tenant = $this->tenantFindService->findTenantByDomain($domain);

        if (!$tenant) {
            Log::info("Tenant not found for domain: $domain");

            throw new TenantNotFoundException();
        }

        $this->tenantSessionService->setTenant($tenant);

        return $tenant;
    }
}
