<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Middleware\Config\CheckValue;
use Modules\Tenant\Actions\TenantDump;
use Modules\Tenant\Actions\TenantsDump;
use Modules\Tenant\Http\Middleware\IsInternalRequest;

/*
| Tenant Web Routes
*/
Route::group([
    'prefix'     => 'tenant',
    'middleware' => [
        IsInternalRequest::class,
    ],
], static function (): void {
    // Dumps Current Tenant
    Route::get('/', TenantDump::class)
        ->name('tenant');

    // Dumps All Tenants
    Route::get('/cache', TenantsDump::class)
        ->middleware(CheckValue::class . ':tenant.tenant_cache.preload,true')
        ->name('tenants.cache');
});
