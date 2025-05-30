<?php

namespace Modules\TenantAdmin\Providers;

use Modules\Tenant\Contracts\TenantConfigProviderInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Provides TenantAdmin module configuration for tenant responses.
 */
class TenantAdminConfigProvider implements TenantConfigProviderInterface
{
    /**
     * Get TenantAdmin module configuration for the tenant.
     *
     * @param Tenant $tenant
     * @return array{config: array<string, mixed>, visibility: array<string, string>}
     */
    public function getConfig(Tenant $tenant): array
    {
        return [
            'config'     => [
                // TenantAdmin specific configuration
            ],
            'visibility' => [
                // TenantAdmin specific visibility
            ],
        ];
    }

    /**
     * Get the priority for this provider.
     *
     * @return int
     */
    public function priority(): int
    {
        return 50; // Normal priority for admin modules
    }
}
