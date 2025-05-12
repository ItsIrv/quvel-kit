<?php

namespace Modules\Tenant\Services;

use Illuminate\Cache\Repository;
use Illuminate\Http\Request;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;

/**
 * Resolves the current tenant based on the incoming request.
 */
class ResolverService
{
    public function __construct(
        private readonly FindService $tenantFindService,
        private readonly RequestPrivacy $requestPrivacyService,
        private readonly Repository $cache,
        private readonly Request $request,
    ) {
    }

    /**
     * Gets the domain to use for tenant resolution.
     * Uses header `X-Tenant-Domain` if the request is internal.
     */
    public function getDomain(): string
    {
        $domain       = $this->request->getHost();
        $customDomain = $this->request->header('X-Tenant-Domain');

        if ($customDomain && $this->requestPrivacyService->isInternalRequest()) {
            return $customDomain;
        }

        return $domain;
    }

    /**
     * Resolve the tenant from cache or database.
     *
     * @throws TenantNotFoundException
     */
    public function resolveTenant(): Tenant
    {
        $domain = $this->getDomain();

        return $this->cache->remember(
            $domain,
            config('tenant.tenant_cache.resolver_ttl'),
            fn (): Tenant => $this->tenantFindService->findTenantByDomain($domain)
            ?? throw new TenantNotFoundException()
        );
    }
}
