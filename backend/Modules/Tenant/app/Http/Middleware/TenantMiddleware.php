<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Services\ConfigurationPipeline;

/**
 * Middleware to resolve the current tenant from the request.
 */
class TenantMiddleware
{
    /**
     * Create a new TenantMiddleware instance.
     */
    public function __construct(
        private readonly TenantResolver $tenantResolver,
        private readonly TenantContext $tenantContext,
        private readonly ConfigurationPipeline $configPipeline,
        private readonly ConfigRepository $config,
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = $this->tenantResolver->resolveTenant();

        $this->tenantContext->set($tenant);

        $this->configPipeline->apply($tenant, $this->config);

        return $next($request);
    }
}
