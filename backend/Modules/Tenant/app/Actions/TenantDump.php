<?php

namespace Modules\Tenant\Actions;

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Http\Resources\TenantDumpResource;

/**
 * Action to dump the current tenant.
 */
class TenantDump
{
    /**
     * Execute the action.
     *
     * @throws TenantNotFoundException
     */
    public function __invoke(TenantContext $tenantContext): TenantDumpResource
    {
        return new TenantDumpResource(
            $tenantContext->get(),
        );
    }
}
