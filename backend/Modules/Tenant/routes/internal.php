<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\Config\CheckValue;
use Modules\Tenant\Actions\TenantProtectedConfig;
use Modules\Tenant\Actions\TenantsDump;
use Modules\Core\Http\Middleware\Security\IsInternalRequest;

/*
| Internal Tenant Routes
| These routes require IsInternalRequest middleware and are used by SSR/internal services
*/

Route::middleware([IsInternalRequest::class])->group(function (): void {
    // Get protected tenant info - /tenant-info/protected
    Route::get('/protected', TenantProtectedConfig::class)
        ->name('tenant.protected');

    // Get all tenants for SSR cache - /tenant-info/cache
    Route::get('/cache', TenantsDump::class)
        ->middleware(CheckValue::class . ':tenant.tenant_cache.preload,true')
        ->name('tenant.cache');
});
