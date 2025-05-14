<?php

namespace Modules\Tenant\Services;

use Illuminate\Http\Request;
use Modules\Tenant\Enums\TenantHeader;

/**
 * Service to check if a request is internal.
 */
class RequestPrivacy
{
    private readonly bool $isInternalRequest;

    public function __construct(private readonly Request $request)
    {
        $this->isInternalRequest = $this->isInternalIP() && $this->isCorrectApiKey();
    }

    /**
     * Checks if the request is internal.
     */
    public function isInternalRequest(): bool
    {
        return $this->isInternalRequest;
    }

    /**
     * Checks if the IP is internal.
     */
    private function isInternalIP(): bool
    {
        if (config('tenant.privacy.disable_ip_check')) {
            return true;
        }

        $ip         = $this->request->ip();
        $trustedIps = config('tenant.privacy.trusted_ips');

        return in_array($ip, $trustedIps, true);
    }

    /**
     * Checks if the request is from a trusted IP.
     */
    private function isCorrectApiKey(): bool
    {
        if (config('tenant.privacy.disable_key_check')) {
            return true;
        }

        return $this->request->header(TenantHeader::SSR_KEY->value) === config('tenant.privacy.ssr_api_key');
    }
}
