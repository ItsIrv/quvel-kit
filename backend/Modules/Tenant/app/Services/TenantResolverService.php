<?php

namespace Modules\Tenant\app\Services;

use Modules\Tenant\app\Models\Tenant;

/**
 * Service to resolve tenants.
 */
class TenantResolverService
{
    public function __construct(
        protected TenantFindService $tenantFindService,
        protected TenantSessionService $tenantSessionService,
    ) {
    }

    /**
     * Resolve the tenant by checking the session first, then the database.
     */
    public function resolveTenant(string $domain): ?Tenant
    {
        if ($this->tenantSessionService->hasTenant()) {
            return $this->tenantSessionService->getTenant();
        }

        $tenant = $this->tenantFindService->findTenantByDomain($domain);

        if ($tenant) {
            $this->tenantSessionService->setTenant($tenant);
        }

        return $tenant;
    }
}
