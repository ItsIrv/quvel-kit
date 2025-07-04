<?php

namespace Modules\Tenant\Services;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Modules\Core\Services\Security\RequestPrivacy;
use Modules\Tenant\Enums\TenantHeader;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
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
        private readonly Application $app,
        private readonly ConfigRepository $config,
        private readonly TenantMemoryCache $memoryCache,
    ) {
    }

    /**
     * Resolve the tenant from memory cache, regular cache, or database.
     */
    public function resolveTenant(): Tenant
    {
        $domain = $this->getHost();

        // First try memory cache (Octane)
        $tenant = $this->memoryCache->getTenant($domain);
        if ($tenant !== null) {
            return $tenant;
        }

        // Fallback to regular resolution
        $tenant = $this->app->environment('local')
            ? $this->resolveTenantFromDatabase()
            : $this->resolveTenantFromCache();

        if ($tenant === null) {
            throw new HttpResponseException(
                response()->noContent(),
                new TenantNotFoundException('Tenant not found for hostname ' . $domain),
            );
        }

        // Cache in memory for future requests
        $this->memoryCache->cacheTenant($domain, $tenant);

        return $tenant;
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
                response()->noContent(),
                new TenantNotFoundException('Tenant not found for hostname ' . $this->getHost()),
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

        if ($customHost !== null && $this->requestPrivacyService->isInternalRequest()) {
            $parsedHost = parse_url($customHost, PHP_URL_HOST);

            if ($parsedHost !== null && $parsedHost !== false && $parsedHost !== '' && $parsedHost !== '0') {
                $this->request->headers->set('host', $parsedHost);
                $this->request->server->set('HTTP_HOST', $parsedHost);

                return $parsedHost;
            }
        }

        return $host;
    }
}
