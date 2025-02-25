<?php

namespace Modules\Tenant\Actions;

use Modules\Tenant\app\Contexts\TenantContext;
use Modules\Tenant\Transformers\TenantDumpTransformer;

/**
 * Action to dump the current tenant.
 */
class TenantDump
{
    /**
     * Execute the action.
     *
     * @param TenantContext $tenantContext
     * @return TenantDumpTransformer
     */
    public function __invoke(TenantContext $tenantContext): TenantDumpTransformer
    {
        return new TenantDumpTransformer(
            $tenantContext->get(),
        );
    }
}
