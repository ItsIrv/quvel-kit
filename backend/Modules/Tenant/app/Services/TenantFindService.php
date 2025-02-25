<?php

namespace Modules\Tenant\app\Services;

use Modules\Tenant\app\Models\Tenant;

class TenantFindService
{
    /**
     * Find a tenant by domain.
     *
     * @return Tenant|null
     */
    public function findTenantByDomain(string $domain): ?Tenant
    {
        /** @phpstan-ignore-next-line  TODO: */
        return Tenant::where('domain', '=', $domain)->first();
    }
}
