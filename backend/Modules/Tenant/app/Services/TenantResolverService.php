<?php

namespace Modules\Tenant\Services;

use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
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
        private readonly RequestPrivacyService $requestPrivacyService,
        private readonly Repository $cache,
    ) {
    }

    /**
     * Resolve the tenant by checking the session first, then the database.
     * If no tenant is found, throws an exception.
     * Also allows for a custom domain to be set through a header when the request is internal.
     *
     * @throws TenantNotFoundException
     */
    public function resolveTenant(Request $request): Tenant
    {
        $domain       = $request->getHost();
        $customDomain = $request->header('X-Tenant-Domain');

        if ($customDomain && $this->requestPrivacyService->isInternalRequest()) {
            $domain = $customDomain;
        }

        $tenant = $this->cache->remember(
            $domain,
            config('tenant.tenant_cache.ttl'),
            fn (): Tenant => $this->tenantFindService->findTenantByDomain(
                $domain,
            ) ?? throw new TenantNotFoundException()
        );

        return $tenant;
    }
}
