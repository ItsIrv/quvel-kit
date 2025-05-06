<?php

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TenantConfigApplier;
use Modules\Tenant\Services\TenantFindService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

if (!function_exists('setTenant')) {
    function setTenant(int $tenantId): void
    {
        $app    = app();
        $tenant = $app->make(TenantFindService::class)->findById($tenantId)
            ?? throw new TenantNotFoundException('Tenant not found');

        $app->make(TenantContext::class)->set($tenant);

        TenantConfigApplier::apply(
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
