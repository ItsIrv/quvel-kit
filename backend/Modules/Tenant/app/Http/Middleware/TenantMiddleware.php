<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Services\ResolverService;

/**
 * Middleware to resolve the current tenant from the request.
 */
class TenantMiddleware
{
    /**
     * Create a new TenantMiddleware instance.
     */
    public function __construct(
        private readonly ResolverService $tenantResolver,
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $tenant = $this->tenantResolver->resolveTenant();

        $this->tenantContext->set($tenant);

        return $next($request);
    }
}
