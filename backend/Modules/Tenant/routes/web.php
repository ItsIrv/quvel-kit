<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Actions\TenantDump;
use Modules\Tenant\Actions\TenantsDump;

/*
| Tenant Web Routes
*/
Route::group([
    'prefix' => 'tenant',
], static function (): void {
    // Dumps Current Tenant
    Route::get('/', TenantDump::class)
        ->name('tenant');

    // Dumps All Tenants
    Route::get('/cache', TenantsDump::class)
        ->name('tenants.cache');
});
