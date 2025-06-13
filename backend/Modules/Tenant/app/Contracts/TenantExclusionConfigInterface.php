<?php

namespace Modules\Tenant\Contracts;

use Modules\Tenant\ValueObjects\TenantExclusionConfig;

/**
 * Contract for tenant exclusion configuration providers.
 */
interface TenantExclusionConfigInterface
{
    /**
     * Get the exclusion configuration.
     *
     * @return TenantExclusionConfig
     */
    public function getConfig(): TenantExclusionConfig;
}
