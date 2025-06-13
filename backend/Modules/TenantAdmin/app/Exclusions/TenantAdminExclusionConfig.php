<?php

namespace Modules\TenantAdmin\Exclusions;

use Modules\Tenant\Contracts\TenantExclusionConfigInterface;
use Modules\Tenant\ValueObjects\TenantExclusionConfig;

/**
 * Exclusion configuration for TenantAdmin module.
 */
class TenantAdminExclusionConfig implements TenantExclusionConfigInterface
{
    /**
     * Get the exclusion configuration.
     *
     * @return TenantExclusionConfig
     */
    public function getConfig(): TenantExclusionConfig
    {
        return new TenantExclusionConfig(
            paths: [],
            patterns: [
                'admin/tenants*',
                'api/admin/tenants*',
            ],
        );
    }
}
