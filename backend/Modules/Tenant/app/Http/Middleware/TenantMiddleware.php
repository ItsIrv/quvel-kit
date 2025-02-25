<?php

namespace Modules\Tenant\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\app\Contexts\TenantContext;
use Modules\Tenant\app\Services\TenantResolverService;

/**
 * Middleware to resolve the tenant based on the domain.
 */
class TenantMiddleware
{
    /**
     * Create a new TenantMiddleware instance.
     *
     * @param \Modules\Tenant\app\Services\TenantResolverService $tenantResolver
     * @param \Modules\Tenant\app\Contexts\TenantContext $tenantContext
     */
    public function __construct(
        protected TenantResolverService $tenantResolver,
        protected TenantContext $tenantContext,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $this->tenantContext->set(
            $this->tenantResolver->resolveTenant(
                $request,
            ),
        );

        return $next($request);
    }
}
