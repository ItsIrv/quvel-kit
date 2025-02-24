<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Actions\TenantDump;

/*
| Tenant Web Routes
*/
Route::group([
    "prefix"     => "tenant",
    "middleware" => ["tenant"],
], function (): void {
    Route::get('/', TenantDump::class)
        ->name('tenant');
});
