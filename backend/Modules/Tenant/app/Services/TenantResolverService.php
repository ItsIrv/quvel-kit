<?php

namespace Modules\Tenant\Services;

use Illuminate\Cache\Repository;
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
        private readonly Repository $cache,
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

        $tenant = $this->cache
            ->remember(
                $domain,
                config('tenant.tenant_cache.ttl'),
                fn (): ?Tenant => $this->tenantFindService->findTenantByDomain($domain)
            );

        if (!$tenant) {
            Log::info("Tenant not found for domain: $domain");

            throw new TenantNotFoundException();
        }

        $this->tenantSessionService->setTenant($tenant);

        return $tenant;
    }
}
