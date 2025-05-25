<?php

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Modules\Tenant\Enums\TenantHeader;

/**
 * Service to check if a request is internal.
 */
class RequestPrivacy
{
    private readonly bool $isInternalRequest;

    public function __construct(
        private readonly Request $request,
        private readonly ConfigRepository $configRepository,
    ) {
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
        if ($this->configRepository->get('tenant.privacy.disable_ip_check')) {
            return true;
        }

        $ip         = $this->request->ip();
        $trustedIps = $this->configRepository->get('tenant.privacy.trusted_ips');

        return in_array($ip, $trustedIps, true);
    }

    /**
     * Checks if the request is from a trusted IP.
     */
    private function isCorrectApiKey(): bool
    {
        if ($this->configRepository->get('tenant.privacy.disable_key_check')) {
            return true;
        }

        return $this->request->header(TenantHeader::SSR_KEY->value)
            === $this->configRepository->get('tenant.privacy.ssr_api_key');
    }
}
