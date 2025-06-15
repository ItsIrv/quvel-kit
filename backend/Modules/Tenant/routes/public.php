<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Http\Controllers\TenantPublicController;

/*
| Public Tenant Routes  
| These routes are accessible without authentication for public config access
*/

// Get public tenant config by domain - /api/tenant-info/public?domain=example.com
Route::get('/public', TenantPublicController::class)->name('tenant.public');