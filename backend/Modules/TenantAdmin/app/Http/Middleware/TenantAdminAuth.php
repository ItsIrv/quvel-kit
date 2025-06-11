<?php

namespace Modules\TenantAdmin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\TenantAdmin\Services\AuthenticationService;
use Symfony\Component\HttpFoundation\Response;

class TenantAdminAuth
{
    public function __construct(
        private AuthenticationService $authService
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!$this->authService->isAuthenticated($request->session())) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect('/admin/tenants/login');
        }

        return $next($request);
    }
}
