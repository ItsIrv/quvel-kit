<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\Tenant\Contexts\TenantContext;

/**
 * Middleware to validate that the session belongs to the current tenant.
 * This prevents cross-tenant session hijacking by ensuring the session's
 * tenant_id matches the current tenant context.
 */
class ValidateTenantSession
{
    /**
     * Create a new ValidateTenantSession instance.
     */
    public function __construct(
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        // Skip validation if tenant context is bypassed
        if ($this->tenantContext->isBypassed()) {
            return $next($request);
        }

        $currentTenant = $this->tenantContext->get();
        $session       = $request->session();

        // If session exists and has a stored tenant_id
        if ($session->has('tenant_id')) {
            $sessionTenantId = $session->get('tenant_id');

            // Check if session tenant matches current tenant
            if ($sessionTenantId !== $currentTenant->id) {
                // Session belongs to different tenant - invalidate it
                $session->invalidate();
                $session->regenerateToken();

                // Log out the user if authenticated
                if (Auth::check()) {
                    Auth::logout();
                }

                // Store the correct tenant_id for new session
                $session->put('tenant_id', $currentTenant->id);
            }
        } else {
            // No tenant_id in session - store current tenant
            $session->put('tenant_id', $currentTenant->id);
        }

        // Ensure authenticated users belong to current tenant
        if (Auth::check()) {
            $user = Auth::user();

            // Check if user belongs to current tenant
            if ($user->tenant_id !== $currentTenant->id) {
                // User from different tenant - log them out
                Auth::logout();
                $session->invalidate();
                $session->regenerateToken();

                // Store the correct tenant_id for new session
                $session->put('tenant_id', $currentTenant->id);
            }
        }

        return $next($request);
    }
}
