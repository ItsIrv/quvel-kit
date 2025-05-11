<?php

namespace Modules\Tenant\Services;

use Illuminate\Http\Request;

/**
 * Service to check if a request is internal.
 */
class RequestPrivacyService
{
    public function __construct(private readonly Request $request)
    {
    }

    /**
     * Checks if the request is internal.
     */
    public function isInternalRequest(): bool
    {
        return $this->isInternalIP() && $this->isCorrectApiKey();
    }

    /**
     * Checks if the IP is internal.
     */
    public function isInternalIP(): bool
    {
        $ip         = $this->request->ip();
        $trustedIps = config('tenant.trusted_ips');

        return in_array($ip, $trustedIps, true);
    }

    /**
     * Checks if the request is from a trusted IP.
     */
    public function isCorrectApiKey(): bool
    {
        return $this->request->header('X-SSR-Key') === config('tenant.ssr_api_key');
    }
}
