<?php

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\ConfigApplier;
use Modules\Tenant\Services\FindService;
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
