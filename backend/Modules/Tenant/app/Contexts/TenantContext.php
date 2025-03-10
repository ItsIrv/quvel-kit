<?php

namespace Modules\Tenant\Contexts;

use Modules\Tenant\Enums\TenantError;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\TenantConfig;

/**
 * This class is used for getting and storing the current tenant from the scoped request cycle.
 */
class TenantContext
{
    /**
     * The current tenant.
     * @var Tenant
     */
    protected Tenant $tenant;

    /**
     * Set the tenant.
     * @param Tenant $tenant
     * @return void
     */
    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the tenant.
     * @return Tenant
     * @throws TenantNotFoundException
     */
    public function get(): Tenant
    {
        if (!isset($this->tenant)) {
            throw new TenantNotFoundException(
                TenantError::NO_CONTEXT_TENANT->value,
            );
        }

        return $this->tenant;
    }

    /**
     * Get the tenant's scoped configuration.
     * @return TenantConfig|null
     */
    public function getConfig(): TenantConfig|null
    {
        return $this->tenant->config;
    }

    /**
     * Get a specific config value.
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->tenant->config->{$key} ?? $default;
    }
}
