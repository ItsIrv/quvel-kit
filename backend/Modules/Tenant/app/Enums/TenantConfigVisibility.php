<?php

namespace Modules\Tenant\Enums;

enum TenantConfigVisibility: string
{
    /**
     * Exposed all the way to the browser window level.
     */
    case PUBLIC = 'public';

    /**
     * Exposed down to the SSR level.
     */
    case PROTECTED = 'protected';

    /**
     * Never exposed. For internal backend use only.
     */
    case PRIVATE = 'private';
}
