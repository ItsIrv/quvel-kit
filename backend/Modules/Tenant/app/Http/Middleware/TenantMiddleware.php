<?php

namespace Modules\Tenant\app\Http\Middleware;

use App\Services\FrontendService;
use Closure;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Tenant\app\Services\TenantResolverService;
use Modules\Tenant\Enums\TenantError;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Middleware to resolve the tenant based on the domain.
 */
class TenantMiddleware
{
    public function __construct(
        protected TenantResolverService $tenantResolver,
        protected FrontendService $frontendService,
    ) {
    }

    /**
     * Handle an incoming request.
     * @throws NotFoundHttpException
     */
    public function handle(Request $request, Closure $next): mixed
    {
        $domain = $request->getHost();
        $tenant = $this->tenantResolver->resolveTenant(
            $domain,
        );

        if (!$tenant) {
            return $this->handleMissingTenant();
        }

        return $next($request);
    }

    /**
     * Handle missing tenant logic.
     * @throws Exception
     */
    protected function handleMissingTenant(): RedirectResponse
    {
        if (app()->environment('local')) {
            throw new Exception(
                TenantError::NOT_FOUND->value,
            );
        }

        return $this->frontendService->redirectError(
            TenantError::NOT_FOUND->value,
        );
    }
}
