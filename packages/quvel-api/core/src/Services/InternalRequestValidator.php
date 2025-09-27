<?php

declare(strict_types=1);

namespace Quvel\Core\Services;

use Quvel\Core\Enums\HttpHeader;
use Illuminate\Http\Request;
use Closure;

/**
 * Service to validate if a request is internal/trusted.
 */
class InternalRequestValidator
{
    /**
     * Custom validation closure.
     */
    protected static ?Closure $customValidator = null;

    /**
     * Set a custom validation closure.
     */
    public static function setValidator(?Closure $validator): void
    {
        static::$customValidator = $validator;
    }

    /**
     * Check if the request is internal/trusted.
     */
    public function isInternalRequest(Request $request): bool
    {
        if (static::$customValidator !== null) {
            return (static::$customValidator)($request);
        }

        return $this->isValidIp($request) && $this->isValidApiKey($request);
    }

    /**
     * Check if the request IP is trusted.
     */
    public function isValidIp(Request $request): bool
    {
        if (config('quvel-core.security.internal_requests.disable_ip_check', false)) {
            return true;
        }

        $ip = $request->ip();
        $trustedIps = config('quvel-core.security.internal_requests.trusted_ips', ['127.0.0.1', '::1']);

        return in_array($ip, $trustedIps, true);
    }

    /**
     * Check if the request has a valid internal API key.
     */
    public function isValidApiKey(Request $request): bool
    {
        if (config('quvel-core.security.internal_requests.disable_key_check', false)) {
            return true;
        }

        $expectedKey = config('quvel-core.security.internal_requests.api_key');

        if (!$expectedKey) {
            return false;
        }

        $providedKey = $request->header(HttpHeader::SSR_KEY->getValue());

        return hash_equals($expectedKey, $providedKey ?? '');
    }
}