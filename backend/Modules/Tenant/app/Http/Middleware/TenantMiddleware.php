<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Services\TenantResolverService;

/**
 * Middleware to resolve the tenant based on the domain.
 */
class TenantMiddleware
{
    /**
     * Create a new TenantMiddleware instance.
     *
     * @param TenantResolverService $tenantResolver
     * @param TenantContext $tenantContext
     */
    public function __construct(
        private readonly TenantResolverService $tenantResolver,
        private readonly TenantContext $tenantContext,
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
