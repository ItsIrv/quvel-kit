<?php

namespace Modules\Tenant\Contracts;

use Modules\Tenant\Models\Tenant;

/**
 * Interface for modules to provide additional tenant configuration
 * that should be included in the tenant API response.
 */
interface TenantConfigProviderInterface
{
    /**
     * Get additional configuration to add to the tenant response.
     * 
     * @param Tenant $tenant
     * @return array{config: array<string, mixed>, visibility: array<string, string>}
     */
    public function getConfig(Tenant $tenant): array;

    /**
     * Get the priority for this provider (higher runs first).
     * 
     * @return int
     */
    public function priority(): int;
}