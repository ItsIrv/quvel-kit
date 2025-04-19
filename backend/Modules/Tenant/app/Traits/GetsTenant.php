<?php

namespace Modules\Tenant\Traits;

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Models\Tenant;

trait GetsTenant
{
    /**
     * Get the resolved tenant.
     */
    public function getTenant(): Tenant
    {
        return app(TenantContext::class)->get();
    }

    /**
     * Get the resolved tenant ID.
     */
    public function getTenantId(): int
    {
        return $this->getTenant()->id;
    }

    /**
     * Get the resolved tenant public ID.
     */
    public function getTenantPublicId(): string
    {
        return $this->getTenant()->public_id;
    }
}
