<?php

namespace Modules\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Modules\Core\Enums\StatusEnum;
use Modules\Tenant\Services\RequestPrivacy;

/**
 * Middleware to check if the request is internal.
 */
class IsInternalRequest
{
    public function __construct(
        private readonly RequestPrivacy $requestPrivacyService,
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
            throw new HttpResponseException(
                new Response(
                    StatusEnum::UNAUTHORIZED->value,
                    401,
                ),
            );
        }

        return $next($request);
    }
}
