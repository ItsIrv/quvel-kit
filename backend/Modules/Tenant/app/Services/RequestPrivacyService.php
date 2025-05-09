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

    public function isInternalRequest(): bool
    {
        $trustedIps = config('tenant.trusted_ips');
        $ip         = $this->request->ip();

        return in_array($ip, $trustedIps, true);
    }
}
