<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Tenant\Services\RequestPrivacyService;

/**
 * Middleware to check if the request is internal.
 */
class IsInternalRequest
{
    public function __construct(
        private readonly RequestPrivacyService $requestPrivacyService,
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
        if (!$this->requestPrivacyService->isInternalRequest()) {
            abort(401);
        }

        return $next($request);
    }
}
