<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\Services\TierService;
use Symfony\Component\HttpFoundation\Response;

class RequiresTierFeature
{
    public function __construct(
        private readonly TierService $tierService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        if (!$this->tierService->currentTenantHasFeature($feature)) {
            abort(403, "Your current plan does not include access to this feature. Please upgrade to access '{$feature}'.");
        }

        return $next($request);
    }
}