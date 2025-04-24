<?php

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TenantFindService;

if (!function_exists('setTenant')) {
    function setTenant(int $tenantId): void
    {
        app(TenantContext::class)->set(
            app(TenantFindService::class)->findById($tenantId),
        );
    }
}

if (!function_exists('getTenant')) {
    function getTenant(): ?Tenant
    {
        return app(TenantContext::class)->get();
    }
}
