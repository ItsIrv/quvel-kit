<?php

namespace Modules\TenantAdmin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\TenantAdmin\Services\InstallationService;
use Symfony\Component\HttpFoundation\Response;

class CheckInstalled
{
    public function __construct(
        private InstallationService $installationService
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If TenantAdmin is not installed, redirect to installation
        if (!$this->installationService->isInstalled()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'TenantAdmin is not installed.',
                    'redirect' => '/admin/tenants/install'
                ], 403);
            }

            return redirect('/admin/tenants/install');
        }

        return $next($request);
    }
}
