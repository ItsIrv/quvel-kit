<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\ConfigGate;
use Modules\Tenant\Actions\TenantDump;
use Modules\Tenant\Actions\TenantsDump;

/*
| Tenant Web Routes
|TODO: Add middleware to check that the tenant config matches the internalApiUrl
    thats a good way to ensure that the tenant endpoints are only accessible internally
    by docker and not from the outside
*/
Route::group([
    'prefix'     => 'tenant',
    'middleware' => ConfigGate::class . ':tenant.multi_tenant,true',
], static function (): void {
    // Dumps Current Tenant
    Route::get('/', TenantDump::class)
        ->name('tenant');

    // Dumps All Tenants
    Route::get('/cache', TenantsDump::class)
        ->middleware(ConfigGate::class . ':tenant.tenant_cache.preload,true')
        ->name('tenants.cache');
});
