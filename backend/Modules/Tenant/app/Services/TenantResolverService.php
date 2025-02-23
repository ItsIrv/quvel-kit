<?php

namespace Modules\Tenant\app\Services;

use Modules\Tenant\app\Models\Tenant;

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
        // Check if tenant is already in session
        if ($this->tenantSessionService->hasTenant()) {
            return $this->tenantSessionService->getTenant();
        }

        // Resolve tenant from database
        $tenant = $this->tenantFindService->findTenantByDomain($domain);

        if ($tenant) {
            $this->tenantSessionService->setTenant($tenant);
        }

        return $tenant;
    }

    /**
     * Clear tenant session.
     */
    public function clearTenant(): void
    {
        $this->tenantSessionService->clearTenant();
    }
}
