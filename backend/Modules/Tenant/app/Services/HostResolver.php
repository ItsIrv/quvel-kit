<?php

namespace Modules\Tenant\Services;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
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
        private readonly FrontendService $frontendService,
        private readonly Application $app,
        private readonly ConfigRepository $config,
    ) {
    }

    /**
     * Resolve the tenant from cache or database.
     */
    public function resolveTenant(): Tenant
    {
        return $this->app->environment('local')
            ? $this->resolveTenantFromDatabase()
            : $this->resolveTenantFromCache();
    }

    protected function resolveTenantFromCache(): ?Tenant
    {
        return $this->cache->remember(
            key: $this->getHost(),
            ttl: $this->config->get('tenant.tenant_cache.resolver_ttl'),
            callback: fn (): Tenant => $this->resolveTenantFromDatabase()
        );
    }

    protected function resolveTenantFromDatabase(): Tenant
    {
        return $this->tenantFindService->findTenantByDomain($this->getHost())
            ?? throw new HttpResponseException(
                $this->frontendService->redirect(''),
            );
    }

    /**
     * Gets the domain to use for tenant resolution.
     * Uses header `X-Tenant-Domain` if the request is internal.
     */
    protected function getHost(): string
    {
        $host       = $this->request->getHost();
        $customHost = $this->request->header(TenantHeader::TENANT_DOMAIN->value);

        if ($customHost && $this->requestPrivacyService->isInternalRequest()) {
            $customHost = parse_url($customHost, PHP_URL_HOST);

            if (!empty($customHost)) {
                $this->request->headers->set('host', $customHost);
                $this->request->server->set('HTTP_HOST', $customHost);

                return $customHost;
            }
        }

        return $host;
    }
}
