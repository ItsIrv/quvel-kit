<?php

namespace Modules\Tenant\app\Contexts;

use Modules\Tenant\app\Models\Tenant;
use Modules\Tenant\app\Exceptions\TenantNotFoundException;
use Modules\Tenant\Enums\TenantError;

class TenantContext
{
    protected Tenant $tenant;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

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
