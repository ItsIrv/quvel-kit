<?php

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\ConfigApplier;
use Modules\Tenant\Services\FindService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

if (!function_exists('setTenant')) {
    function setTenant(int $tenantId): void
    {
        $app    = app();
        $tenant = $app->make(FindService::class)->findById($tenantId)
            ?? throw new TenantNotFoundException('Tenant not found');

        $app->make(TenantContext::class)->set($tenant);

        ConfigApplier::apply(
            $tenant,
            $app->make(ConfigRepository::class),
        );
    }
}

if (!function_exists('getTenant')) {
    function getTenant(): Tenant
    {
        return app(TenantContext::class)->get();
    }
}
