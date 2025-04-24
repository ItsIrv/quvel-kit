<?php

namespace Modules\Tenant\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TenantFindService;
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
        // In console environment, try to get tenant from the model if available
        if (app()->runningInConsole() && $this instanceof Model && isset($this->tenant_id)) {
            return $this->getTenantFindService()->findById($this->tenant_id);
        }

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
     * Get the TenantFindService instance
     *
     * @return TenantFindService
     */
    protected function getTenantFindService(): TenantFindService
    {
        return app(TenantFindService::class);
    }
}
