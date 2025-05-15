<?php

namespace Modules\Tenant\Contracts;

use Modules\Tenant\Models\Tenant;

/**
 * Tenant resolver interface.
 */
interface TenantResolver
{
    /**
     * Resolves the current tenant.
     *
     * @return Tenant The resolved tenant.
     */
    public function resolveTenant(): Tenant;
}
