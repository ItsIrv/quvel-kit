<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\Services\TenantExclusionRegistry;

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
        private readonly TenantExclusionRegistry $exclusionRegistry,
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Check if the current route should bypass tenant resolution
        if ($this->shouldBypassTenant($request)) {
            return $next($request);
        }

        $tenant = $this->tenantResolver->resolveTenant();

        $this->tenantContext->set($tenant);

        $this->configPipeline->apply($tenant, $this->config);

        return $next($request);
    }

    /**
     * Determine if the request should bypass tenant resolution.
     */
    protected function shouldBypassTenant(Request $request): bool
    {
        // First check the registry for dynamically registered exclusions
        $registeredPaths    = $this->exclusionRegistry->getExcludedPaths();
        $registeredPatterns = $this->exclusionRegistry->getExcludedPatterns();

        // Then check config for any static exclusions
        $configPaths    = $this->config->get('tenant.excluded_paths', []);
        $configPatterns = $this->config->get('tenant.excluded_patterns', []);

        // Combine all exclusions
        $allPaths    = array_merge($registeredPaths, $configPaths);
        $allPatterns = array_merge($registeredPatterns, $configPatterns);

        // Check exact path matches
        foreach ($allPaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        // Check pattern matches
        foreach ($allPatterns as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }
}
