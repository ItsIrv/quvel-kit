<?php

namespace Modules\Tenant\Enums;

/**
 * Headers available for the tenant module.
 */
enum TenantHeader: string
{
    /**
     * Override the tenant domain.
     * Request must be internal or this header will be ignored.
     */
    case TENANT_DOMAIN = 'X-Tenant-Domain';
}
