<?php

namespace Modules\Tenant\Enums;

enum TenantError: string
{
    case NOT_FOUND         = 'errors.tenant_not_found';
    case NO_CONTEXT_TENANT = 'errors.no_active_tenant';
}
