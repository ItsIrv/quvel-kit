<?php

namespace Modules\Tenant\Contracts;

use Modules\Tenant\ValueObjects\TenantTableConfig;

/**
 * Contract for tenant table configuration providers.
 */
interface TenantTableConfigInterface
{
    /**
     * Get the table configuration.
     *
     * @return TenantTableConfig
     */
    public function getConfig(): TenantTableConfig;
}
