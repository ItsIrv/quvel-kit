<?php

namespace Modules\Core\Http\Middleware\Trace;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Modules\Core\Enums\CoreHeader;
use Modules\Core\Services\Security\RequestPrivacy;

/**
 * Middleware to capture and propagate trace IDs for distributed tracing.
 */
class SetTraceId
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(protected RequestPrivacy $requestPrivacy)
    {
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!config('core.trace.enabled')) {
            return $next($request);
        }

        // Check if we should accept the trace ID from the request
        $headerTraceId      = $request->header(CoreHeader::TRACE_ID->value);
        $shouldAcceptHeader = $this->shouldAcceptTraceHeader($headerTraceId);

        // Get trace ID from request header if allowed, or generate a new one
        $traceId = $shouldAcceptHeader ? $headerTraceId : (string) Str::uuid();

        // If trace ID is still empty and generation is enabled, create a new one
        if (empty($traceId) && config('core.trace.always_generate', true)) {
            $traceId = (string) Str::uuid();
        }

        // Store trace ID in context for use throughout the request lifecycle
        Context::add('trace_id', $traceId);

        // Add trace ID to response headers for client-side correlation
        $response = $next($request);
        $response->headers->set(CoreHeader::TRACE_ID->value, $traceId);

        return $response;
    }

    /**
     * Determine if we should accept the trace header from the request.
     */
    protected function shouldAcceptTraceHeader(?string $traceId): bool
    {
        // If no trace ID is provided, no need to check
        if (empty($traceId)) {
            return false;
        }

        // If we don't require internal requests, always accept the header
        if (!config('core.trace.require_internal_request', true)) {
            return true;
        }

        // Otherwise, only accept from internal requests
        return $this->requestPrivacy->isInternalRequest();
    }
}
