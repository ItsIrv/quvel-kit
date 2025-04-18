<?php

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Models\Tenant;

if (!function_exists('setTenant')) {
    function setTenant(int $tenantId): void
    {
        app(TenantContext::class)->set(
            Tenant::findOrFail($tenantId),
        );
    }
}

if (!function_exists('getTenant')) {
    function getTenant(): ?Tenant
    {
        return app(TenantContext::class)->get();
    }
}
