<?php

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\FindService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\Services\ConfigurationPipeline;

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

        if ($tenant === null) {
            throw new TenantNotFoundException('Tenant not found');
        }

        $app->make(TenantContext::class)->set($tenant);

        $app->make(ConfigurationPipeline::class)->apply(
            $tenant,
            $app->make(ConfigRepository::class),
        );

        return true;
    }
}

if (!function_exists('setTenantContext')) {
    /**
     * Set the current tenant context without applying configuration.
     * Useful for seeders and tests where you need to switch tenants
     * without changing database connections or other configurations.
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
    function setTenantContext(mixed $variant): bool
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

        if ($tenant === null) {
            throw new TenantNotFoundException('Tenant not found');
        }

        $app->make(TenantContext::class)->set($tenant);

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
        if ($config === null) {
            return $default;
        }

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


if (!function_exists('createTenantConfig')) {
    /**
     * Create a new DynamicTenantConfig instance.
     * Useful for CLI/Tinker when setting up tenants.
     *
     * @param array<string, mixed> $data Configuration data
     * @param array<string, mixed> $visibility Visibility settings
     * @return DynamicTenantConfig
     */
    function createTenantConfig(array $data = [], array $visibility = []): DynamicTenantConfig
    {
        return new DynamicTenantConfig($data, $visibility);
    }
}
