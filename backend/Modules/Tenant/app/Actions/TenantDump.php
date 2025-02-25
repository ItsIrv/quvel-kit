<?php

namespace Modules\Tenant\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Log;
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
        return new TenantDumpTransformer($tenantContext->get());
    }
}
