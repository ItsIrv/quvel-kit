<?php

namespace Modules\Tenant\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
        protected readonly TenantResolverService $tenantResolver,
        protected readonly TenantContext $tenantContext,
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

        Log::info('Tenant resolved: ', $this->tenantContext->get()->toArray());

        return $next($request);
    }
}
