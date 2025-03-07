<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Actions\TenantDump;
use Modules\Tenant\Actions\TenantsDump;

/*
| Tenant Web Routes
*/
Route::group([
    "prefix" => "tenant",
], function (): void {
    Route::get('/', TenantDump::class)
        ->name('tenant');

    Route::get('/cache', TenantsDump::class)
        ->name('tenants.cache');
});
