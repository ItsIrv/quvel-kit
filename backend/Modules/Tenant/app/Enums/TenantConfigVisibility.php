<?php

namespace Modules\Tenant\Enums;

enum TenantConfigVisibility: string
{
    /**
     * Exposed all the way to the browser window level.
     */
    case PUBLIC = 'public';

    /**
     * Exposed to SSR context only.
     */
    case PROTECTED = 'protected';

    /**
     * Never exposed publicly. For internal backend use only.
     */
    case PRIVATE = 'private';
}
