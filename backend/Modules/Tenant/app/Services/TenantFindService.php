<?php

namespace Modules\Tenant\app\Services;

use Modules\Tenant\app\Models\Tenant;

class TenantFindService
{
    /**
     * Find a tenant by domain from the database.
     */
    public function findTenantByDomain(string $domain): ?Tenant
    {
        return Tenant::where('domain', $domain)->first();
    }
}
