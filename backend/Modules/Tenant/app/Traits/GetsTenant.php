<?php

namespace Modules\Tenant\Traits;

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\FindService;
use RuntimeException;

trait GetsTenant
{
    /**
     * Get the resolved tenant.
     *
     * @throws RuntimeException When no tenant can be resolved
     */
    protected function getTenant(): Tenant
    {
        // Otherwise use the tenant context
        return app(TenantContext::class)->get();
    }

    /**
     * Get the resolved tenant ID.
     */
    protected function getTenantId(): int
    {
        return $this->getTenant()->id;
    }

    /**
     * Get the resolved tenant public ID.
     */
    protected function getTenantPublicId(): string
    {
        return $this->getTenant()->public_id;
    }

    /**
     * Get the FindService instance
     */
    protected function getTenantFindService(): FindService
    {
        return app(FindService::class);
    }
}
