<?php

namespace Modules\TenantAdmin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\TenantAdmin\Services\InstallationService;
use Symfony\Component\HttpFoundation\Response;

class CheckNotInstalled
{
    public function __construct(
        private InstallationService $installationService,
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // If TenantAdmin is already installed, redirect to login
        if ($this->installationService->isInstalled()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message'  => 'TenantAdmin is already installed.',
                    'redirect' => '/tenant/admin/login',
                ], 403);
            }

            return redirect('/tenant/admin/login');
        }

        return $next($request);
    }
}
