<?php

namespace Modules\Tenant\Services;

use Illuminate\Cache\Repository;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Modules\Tenant\Enums\TenantHeader;
use Modules\Tenant\Models\Tenant;
use Modules\Core\Services\FrontendService;
use Modules\Tenant\Contracts\TenantResolver;

/**
 * Resolves the current tenant based on the incoming request host.
 */
class HostResolver implements TenantResolver
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
    public function getHost(): string
    {
        $host       = $this->request->getHost();
        $customHost = parse_url(
            $this->request->header(TenantHeader::TENANT_DOMAIN->value),
            PHP_URL_HOST,
        );

        if ($customHost && $this->requestPrivacyService->isInternalRequest()) {
            $this->request->headers->set('host', $customHost);
            $this->request->server->set('HTTP_HOST', $customHost);

            return $customHost;
        }

        return $host;
    }

    /**
     * Resolve the tenant from cache or database.
     */
    public function resolveTenant(): Tenant
    {
        $host = $this->getHost();

        return $this->cache->remember(
            $host,
            config('tenant.tenant_cache.resolver_ttl'),
            fn (): Tenant => $this->tenantFindService->findTenantByDomain($host)
            ?? throw new HttpResponseException(
                app(FrontendService::class)->redirect(''),
            )
        );
    }
}
