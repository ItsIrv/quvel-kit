<?php

namespace Modules\Tenant\app\Services;

use Modules\Tenant\app\Models\Tenant;
use Modules\Tenant\app\Models\TenantDomain;

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
