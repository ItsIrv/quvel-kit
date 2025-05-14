<?php

namespace Modules\Tenant\Services;

use Illuminate\Http\Request;
use Modules\Tenant\Enums\TenantHeader;

/**
 * Service to check if a request is internal.
 */
class RequestPrivacy
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
        logger()->debug('TenantRequestPrivacyService: Checking internal IP: ' . $this->request->ip());

        if (config('tenant.privacy.disable_ip_check')) {
            logger()->debug('TenantRequestPrivacyService: IP check disabled');
            return true;
        }

        $ip         = $this->request->ip();
        $trustedIps = config('tenant.privacy.trusted_ips');

        logger()->debug('TenantRequestPrivacyService: IP check: ' . $ip . ' is in ' . json_encode($trustedIps));

        $result = in_array($ip, $trustedIps, true);

        logger()->debug('TenantRequestPrivacyService: IP check result: ' . ($result ? 'true' : 'false'));

        return $result;
    }

    /**
     * Checks if the request is from a trusted IP.
     */
    public function isCorrectApiKey(): bool
    {
        logger()->debug('TenantRequestPrivacyService: Checking internal API key: ' . $this->request->header(TenantHeader::SSR_KEY->value));

        if (config('tenant.privacy.disable_key_check')) {
            logger()->debug('TenantRequestPrivacyService: API key check disabled');
            return true;
        }

        $apiKey      = $this->request->header(TenantHeader::SSR_KEY->value);
        $expectedKey = config('tenant.privacy.ssr_api_key');

        logger()->debug('TenantRequestPrivacyService: API key check: ' . $apiKey . ' === ' . $expectedKey);

        $result = $apiKey === $expectedKey;

        logger()->debug('TenantRequestPrivacyService: API key check result: ' . ($result ? 'true' : 'false'));

        return $result;
    }
}
