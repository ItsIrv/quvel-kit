<?php

namespace Modules\Tenant\Actions;

use Illuminate\Http\Request;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Http\Resources\TenantDumpResource;

/**
 * Action to get protected tenant configuration.
 */
class TenantProtectedConfig
{
    /**
     * Execute the action.
     *
     * @throws TenantNotFoundException
     */
    public function __invoke(Request $request): TenantDumpResource
    {
        /**
         * @var TenantResolver $resolver
         */
        $resolver = app(config('tenant.resolver'));

        $tenant = $resolver->resolveTenant();

        return new TenantDumpResource($tenant);
    }
}
