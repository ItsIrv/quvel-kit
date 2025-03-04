<?php

namespace Modules\Tenant\app\Traits;

use Modules\Tenant\app\Contexts\TenantContext;
use Modules\Tenant\app\Models\Tenant;

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
