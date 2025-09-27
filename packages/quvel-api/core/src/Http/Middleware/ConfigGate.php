<?php

declare(strict_types=1);

namespace Quvel\Core\Http\Middleware;

use Quvel\Core\Services\RedirectService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


/**
 * Middleware to gate features based on configuration values.
 * Useful for feature toggles, tenant-specific features, and paid/free tiers.
 */
class ConfigGate
{
    public function __construct(
        private readonly RedirectService $redirectService
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * Usage: ->middleware('config-gate:feature.enabled,true')
     * Usage: ->middleware('config-gate:tenant.plan,premium,Feature not available')
     *
     * @param  string  $configKey The configuration key to check
     * @param  string  $expectedValue The expected value (will be parsed to appropriate type)
     * @param  string|null  $customMessage Optional custom message for access denied
     */
    public function handle(
        Request $request,
        Closure $next,
        string $configKey,
        string $expectedValue,
        ?string $customMessage = null
    ): Response {
        $actualValue = config($configKey);
        
        $expected = $this->parseExpectedValue($expectedValue);

        if ($this->valuesDiffer($actualValue, $expected)) {
            return $this->denyResponse($request, $configKey, $customMessage);
        }

        return $next($request);
    }

    /**
     * Parse the expected value from string to appropriate type.
     * @return string|int|bool|null The parsed value
     */
    private function parseExpectedValue(string $expected): bool|int|string|null
    {
        return match (strtolower($expected)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => is_numeric($expected) ? (int) $expected : $expected,
        };
    }

    /**
     * Type-safe comparison of values.
     */
    private function valuesDiffer(mixed $actual, mixed $expected): bool
    {
        if (is_bool($expected) || is_bool($actual)) {
            return (bool)$actual !== (bool)$expected;
        }

        if ($expected === null || $actual === null) {
            return $actual !== $expected;
        }

        return $actual != $expected;
    }

    /**
     * Generate the denied response.
     */
    private function denyResponse(Request $request, string $configKey, ?string $customMessage): Response
    {
        $message = $customMessage ?? __('quvel-core::messages.config_gate.feature_not_available');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'feature_gated' => true,
                'config_key' => $configKey,
            ], 403);
        }

        return $this->redirectService->redirectWithMessage('', $message);
    }
}