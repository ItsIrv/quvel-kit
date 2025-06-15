<?php

namespace Modules\Tenant\Actions;

use Illuminate\Http\Request;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Http\Resources\TenantDumpResource;
use Modules\Tenant\Services\FindService;

/**
 * Action to dump tenant information.
 * Can use current tenant context or find by domain.
 */
class TenantDump
{
    public function __construct(
        private readonly FindService $findService,
    ) {
    }

    /**
     * Execute the action.
     *
     * @throws TenantNotFoundException
     */
    public function __invoke(Request $request): TenantDumpResource
    {
        // Check if a domain is specified in the request header
        $domain = $request->header('X-Tenant-Domain');

        if ($domain !== null && $domain !== '') {
            // Find tenant by domain
            $tenant = $this->findService->findTenantByDomain($domain);

            if ($tenant === null) {
                throw new TenantNotFoundException("Tenant not found for domain: {$domain}");
            }

            return new TenantDumpResource($tenant);
        }

        throw new TenantNotFoundException("Tenant not found for domain: {$domain}");
    }
}
