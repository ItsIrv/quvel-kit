<?php

namespace Modules\Tenant\Traits;

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Models\Tenant;

trait GetsTenant
{
    /**
     * Get the resolved tenant from the request.
     *
     * This method is isolated for easier testing.
     */
    public static function getTenant(): Tenant
    {
        return app(TenantContext::class)->get();
    }
}
