<?php

declare(strict_types=1);

namespace Quvel\Core\Http\Middleware;

use Quvel\Core\Services\InternalRequestValidator;
use Closure;
use Illuminate\Http\Request;

/**
 * Middleware to ensure request is from an internal/trusted source.
 * Protects internal-only endpoints.
 */
class RequireInternalRequest
{
    public function __construct(
        private readonly InternalRequestValidator $validator
    ) {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!$this->validator->isInternalRequest($request)) {
            abort(401, 'Unauthorized');
        }

        return $next($request);
    }
}