<?php

namespace Modules\Tenant\Contexts;

use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Enums\TenantError;

/**
 * This class is used for getting and storing the current tenant from the scoped request cycle.
 */
class TenantContext
{
    /**
     * The current tenant.
     * @var Tenant
     */
    protected Tenant $tenant;

    /**
     * Set the tenant.
     * @param \Modules\Tenant\app\Models\Tenant $tenant
     * @return void
     */
    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the tenant.
     * @return \Modules\Tenant\app\Models\Tenant
     * @throws \Modules\Tenant\app\Exceptions\TenantNotFoundException
     */
    public function get(): Tenant
    {
        if (!isset($this->tenant)) {
            throw new TenantNotFoundException(
                TenantError::NO_CONTEXT_TENANT->value,
            );
        }

        return $this->tenant;
    }
}
