<?php

namespace Modules\Tenant\Enums;

enum TenantError: string
{
    case NOT_FOUND         = 'tenant.errors.tenant_not_found';
    case NO_CONTEXT_TENANT = 'tenant.errors.no_active_tenant';
    case TENANT_MISMATCH   = 'tenant.errors.tenant_mismatch';
}
