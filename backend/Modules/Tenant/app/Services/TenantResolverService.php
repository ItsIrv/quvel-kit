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
     */
    public function __construct(
        private readonly TenantFindService $tenantFindService,
        private readonly TenantSessionService $tenantSessionService,
    ) {
    }

    /**
     * Resolve the tenant by checking the session first, then the database.
     * If no tenant is found, throws an exception.
     *
     * @throws TenantNotFoundException
     */
    public function resolveTenant(Request $request): Tenant
    {
        $domain = $request->getHost();
        $tenant = $this->tenantSessionService->getTenant();

        if ($tenant) {
            if ($tenant->domain !== $domain) {
                Log::info("Tenant domain mismatch: {$tenant->domain} != {$domain}");

                throw new TenantNotFoundException();
            }

            return $tenant;
        }

        // TODO: Add a check for Cache.
        $tenant = $this->tenantFindService->findTenantByDomain($domain);

        if (!$tenant) {
            Log::info("Tenant not found for domain: $domain");

            throw new TenantNotFoundException();
        }

        $this->tenantSessionService->setTenant($tenant);

        return $tenant;
    }
}
