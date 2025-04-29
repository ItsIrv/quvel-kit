<?php

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Providers\TenantServiceProvider;
use Modules\Tenant\Services\TenantFindService;

if (!function_exists('setTenant')) {
    function setTenant(int $tenantId): void
    {
        $tenant = app(TenantFindService::class)->findById($tenantId)
            ?? throw new TenantNotFoundException('Tenant not found');

        app(TenantContext::class)->set($tenant);

        TenantServiceProvider::applyTenantConfig($tenant);
    }
}

if (!function_exists('getTenant')) {
    function getTenant(): Tenant
    {
        return app(TenantContext::class)->get();
    }
}
