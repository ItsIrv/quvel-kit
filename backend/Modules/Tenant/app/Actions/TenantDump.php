<?php

namespace Modules\Tenant\Actions;

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Transformers\TenantDumpTransformer;

/**
 * Action to dump the current tenant.
 */
class TenantDump
{
    /**
     * Execute the action.
     */
    public function __invoke(TenantContext $tenantContext): TenantDumpTransformer
    {
        return new TenantDumpTransformer(
            $tenantContext->get(),
        );
    }
}
