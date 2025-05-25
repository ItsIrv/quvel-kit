<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Services\TierService;
use Symfony\Component\HttpFoundation\Response;

class RequiresMinimumTier
{
    public function __construct(
        private readonly TierService $tierService,
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $minimumTier): Response
    {
        $tenant = $this->tenantContext->get();

        if (!$this->tierService->meetsMinimumTier($tenant, $minimumTier)) {
            $currentTier = $tenant->config?->getTier() ?? 'basic';
            abort(403, "This feature requires at least '{$minimumTier}' tier. Your current tier is '{$currentTier}'.");
        }

        return $next($request);
    }
}
