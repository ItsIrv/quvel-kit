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
        // TODO: When the host matches the internalApiUrl in tenant config we need to
        //       allow $domain to be set through a header. This allows internal docker
        //       requests to be made under 1 internal domain but still resolve the
        //       correct tenant.
        //       On top of that we should add an optional key mechanism to ensure that
        //       only validated actors can access the tenant endpoints.
        //       If the internalApiUrl is the same as the appUrl the former won't protect
        //       against external requests.
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
