<?php

namespace Modules\Tenant\Enums;

use App\Traits\TranslatableEnum;

enum TenantError: string
{
    use TranslatableEnum;

    case NOT_FOUND = 'tenant.errors.tenant_not_found';
    case NO_CONTEXT_TENANT = 'tenant.errors.no_active_tenant';
    case TENANT_MISMATCH = 'tenant.errors.tenant_mismatch';
}
