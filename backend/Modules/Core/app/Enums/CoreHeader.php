<?php

namespace Modules\Core\Enums;

/**
 * Headers available for the core module.
 */
enum CoreHeader: string
{
    /**
     * Header to set the locale.
     */
    case ACCEPT_LANGUAGE = 'Accept-Language';

    /**
     * Header for distributed tracing ID.
     */
    case TRACE_ID = 'X-Trace-ID';

    /**
     * Header for tenant ID in distributed tracing.
     */
    case TENANT_ID = 'X-Tenant-ID';
}
