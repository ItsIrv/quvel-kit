<?php

namespace Modules\Tenant\Actions;

use Illuminate\Http\Request;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Http\Resources\TenantDumpResource;

/**
 * Action to get public tenant configuration for the current host.
 * Only returns tenant config if the tenant allows public config API access.
 */
class TenantPublicConfig
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

        // Check if tenant allows public config API
        $allowPublicConfig = $tenant->getEffectiveConfig()?->get('allow_public_config_api', false);
        if ($allowPublicConfig !== true) {
            throw new TenantNotFoundException('Public config API not enabled for this tenant');
        }

        return (new TenantDumpResource($tenant))->setPublicAccess(true);
    }
}
