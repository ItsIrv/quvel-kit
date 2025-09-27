<?php

declare(strict_types=1);

namespace Quvel\Core\Http\Middleware;

use Quvel\Core\Enums\HttpHeader;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;


/**
 * Middleware to capture and propagate trace IDs for distributed tracing.
 * Uses UUID by default with optional custom generation/validation.
 */
class TraceMiddleware
{
    /**
     * Custom trace ID generator.
     */
    protected static ?Closure $customGenerator = null;

    /**
     * Custom trace ID validator.
     */
    protected static ?Closure $customValidator = null;

    /**
     * Set a custom trace ID generator.
     */
    public static function setGenerator(Closure $generator): void
    {
        static::$customGenerator = $generator;
    }

    /**
     * Set a custom trace ID validator.
     */
    public static function setValidator(Closure $validator): void
    {
        static::$customValidator = $validator;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (!config('quvel-core.tracing.enabled', true)) {
            return $next($request);
        }

        $traceId = $this->getOrGenerateTraceId($request);

        Context::add('trace_id', $traceId);

        $response = $next($request);

        $response->headers->set(HttpHeader::TRACE_ID->getValue(), $traceId);

        return $response;
    }

    /**
     * Get trace ID from request or generate new one.
     */
    private function getOrGenerateTraceId(Request $request): string
    {
        $headerTraceId = $request->header(HttpHeader::TRACE_ID->getValue());

        if ($headerTraceId && $this->shouldAcceptTraceHeader($request, $headerTraceId)) {
            return (string) $headerTraceId;
        }

        return $this->generateTraceId();
    }

    /**
     * Determine if we should accept the trace header from request.
     */
    private function shouldAcceptTraceHeader(Request $request, string $traceId): bool
    {
        if (empty($traceId)) {
            return false;
        }

        if (static::$customValidator !== null) {
            return (static::$customValidator)($traceId, $request);
        }

        return Str::isUuid($traceId);
    }

    /**
     * Generate a new trace ID.
     */
    private function generateTraceId(): string
    {
        if (static::$customGenerator !== null) {
            return (static::$customGenerator)();
        }

        return (string) Str::uuid();
    }
}