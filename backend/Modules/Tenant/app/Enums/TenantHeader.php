<?php

namespace Modules\Tenant\Enums;

/**
 * Headers available for the tenant module.
 */
enum TenantHeader: string
{
    /**
     * Set the SSR API key.
     * Used when checking if the request is internal.
     */
    case SSR_KEY = 'X-SSR-Key';

    /**
     * Override the tenant domain.
     * Request must be internal or this header will be ignored.
     */
    case TENANT_DOMAIN = 'X-Tenant-Domain';
}
