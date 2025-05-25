<?php

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\ConfigApplier;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\Enums\TenantConfigVisibility;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;

if (!function_exists('setTenant')) {
    /**
     * Set the current tenant context.
     *
     * The variant can be a:
     *  - int: Tenant ID
     *  - string: Tenant domain
     *  - Tenant: Tenant instance
     *
     * @param int|string|Tenant $variant
     * @throws TenantNotFoundException
     * @return bool
     */
    function setTenant(mixed $variant): bool
    {
        $app    = app(Application::class);
        $tenant = null;

        if (is_int($variant)) {
            $tenant = $app->make(FindService::class)->findById($variant);
        } elseif (is_string($variant)) {
            $tenant = $app->make(FindService::class)->findTenantByDomain($variant);
        } elseif ($variant instanceof Tenant) {
            $tenant = $variant;
        } else {
            throw new TenantNotFoundException('Tenant not found.');
        }

        $app->make(TenantContext::class)->set($tenant);

        ConfigApplier::apply(
            $tenant,
            $app->make(ConfigRepository::class),
        );

        return true;
    }
}

if (!function_exists('getTenant')) {
    /**
     * Get the current tenant context.
     *
     * @return Tenant
     */
    function getTenant(): Tenant
    {
        return app(TenantContext::class)->get();
    }
}

if (!function_exists('getTenantConfig')) {
    /**
     * Get the current tenant's configuration.
     * This returns the effective configuration including inherited values.
     *
     * @param string|null $key Optional key to get specific config value
     * @param mixed $default Default value if key doesn't exist
     * @return DynamicTenantConfig|mixed
     */
    function getTenantConfig(?string $key = null, mixed $default = null): mixed
    {
        $config = getTenant()->getEffectiveConfig();
        
        if ($key === null) {
            return $config;
        }
        
        return $config->get($key, $default);
    }
}

if (!function_exists('setTenantConfig')) {
    /**
     * Set a configuration value for the current tenant.
     * Note: This updates the tenant in memory but does NOT persist to database.
     * Call $tenant->save() to persist changes.
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @param TenantConfigVisibility|string|null $visibility Visibility level (defaults to PRIVATE)
     * @return void
     */
    function setTenantConfig(string $key, mixed $value, TenantConfigVisibility|string|null $visibility = null): void
    {
        $tenant = getTenant();
        $config = $tenant->config ?? new DynamicTenantConfig();
        
        $config->set($key, $value);
        
        if ($visibility !== null) {
            $visibilityEnum = is_string($visibility) 
                ? TenantConfigVisibility::from($visibility) 
                : $visibility;
            $config->setVisibility($key, $visibilityEnum);
        }
        
        $tenant->config = $config;
    }
}

if (!function_exists('getTenantTier')) {
    /**
     * Get the current tenant's tier.
     *
     * @return string
     */
    function getTenantTier(): string
    {
        return getTenant()->config?->getTier() ?? 'basic';
    }
}

if (!function_exists('createTenantConfig')) {
    /**
     * Create a new DynamicTenantConfig instance.
     * Useful for CLI/Tinker when setting up tenants.
     *
     * @param array $data Configuration data
     * @param array $visibility Visibility settings
     * @param string $tier Tenant tier (basic, standard, premium, enterprise)
     * @return DynamicTenantConfig
     */
    function createTenantConfig(array $data = [], array $visibility = [], string $tier = 'basic'): DynamicTenantConfig
    {
        return new DynamicTenantConfig($data, $visibility, $tier);
    }
}
