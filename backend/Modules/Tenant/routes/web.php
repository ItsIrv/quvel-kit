<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\TenantController;

/*
| Tenant Web Routes
*/
Route::group([
    "prefix"     => "tenant",
    "middleware" => ["tenant"],
], function (): void {
    Route::resource('/', TenantController::class)
        ->only(['index'])
        ->names('tenant');
});
