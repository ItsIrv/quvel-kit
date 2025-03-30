<?php

namespace Modules\Tenant\Enums;

enum TenantConfigVisibility: string
{
    /**
     * Exposed all the way to the browser window level with window.__TENANT_CONFIG__.
     */
    case PUBLIC = 'public';

    /**
     * Exposed down to the SSR server level. Saved in src-ssr/services/TenantCache.
     */
    case PROTECTED = 'protected';

    /**
     * Never exposed. For internal backend use only.
     */
    case PRIVATE = 'private';
}
