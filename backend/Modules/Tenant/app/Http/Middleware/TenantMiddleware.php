<?php

namespace Modules\Tenant\app\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\app\Services\TenantResolverService;
use App\Services\FrontendService;
use Illuminate\Support\Facades\App;

class TenantMiddleware
{
    protected TenantResolverService $tenantResolver;
    protected FrontendService $frontendService;

    public function __construct(TenantResolverService $tenantResolver, FrontendService $frontendService)
    {
        $this->tenantResolver  = $tenantResolver;
        $this->frontendService = $frontendService;
    }

    public function handle(Request $request, Closure $next): mixed
    {
        $domain = $request->getHost();
        $tenant = $this->tenantResolver->resolveTenant($domain);

        if (!$tenant) {
            if (App::environment('local')) {
                abort(404, "Tenant not found.");
            }

            return $this->frontendService->redirectError("Tenant not found");
        }

        $request->attributes->set('tenant', $tenant);

        return $next($request);
    }
}
