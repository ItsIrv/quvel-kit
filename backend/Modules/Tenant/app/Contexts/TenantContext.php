<?php

namespace Modules\Tenant\Contexts;

use Modules\Tenant\Enums\TenantError;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

/**
 * This class is used for getting and storing the current tenant from the scoped request cycle.
 */
class TenantContext
{
    /**
     * The current tenant.
     */
    protected Tenant $tenant;

    /**
     * Set the tenant.
     */
    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    /**
     * Get the tenant.
     *
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
     */
    public function getConfig(): DynamicTenantConfig|null
    {
        return $this->tenant->config;
    }

    /**
     * Get a specific config value.
     */
    public function getConfigValue(string $key, mixed $default = null): mixed
    {
        $config = $this->tenant->config;

        if ($config instanceof DynamicTenantConfig) {
            return $config->get($key, $default);
        }

        return $config->{$key} ?? $default;
    }
}
