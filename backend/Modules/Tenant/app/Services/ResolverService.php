<?php

namespace Modules\Tenant\Services;

use Illuminate\Cache\Repository;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Modules\Tenant\Enums\TenantHeader;
use Modules\Tenant\Models\Tenant;
use Modules\Core\Services\FrontendService;

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
        $customDomain = parse_url(
            $this->request->header(TenantHeader::TENANT_DOMAIN->value),
            PHP_URL_HOST,
        );

        logger()->debug('TenantResolverService: Requested custom domain: ' . $customDomain);

        if ($customDomain && $this->requestPrivacyService->isInternalRequest()) {
            logger()->debug('TenantResolverService: Using custom domain: ' . $customDomain);
            return $customDomain;
        } else {
            logger()->debug('TenantResolverService: Internal request failed, using getHost domain: ' . $domain);
        }

        return $domain;
    }

    /**
     * Resolve the tenant from cache or database.
     */
    public function resolveTenant(): Tenant
    {
        $domain = $this->getDomain();

        return $this->cache->remember(
            $domain,
            config('tenant.tenant_cache.resolver_ttl'),
            fn (): Tenant => $this->tenantFindService->findTenantByDomain($domain)
            ?? throw new HttpResponseException(
                app(FrontendService::class)->redirect(''),
            )
        );
    }
}
