<?php

use Illuminate\Support\Facades\Route;
use Modules\Tenant\Actions\TenantPublicConfig;

/*
| Public Tenant Routes
| These routes are accessible without authentication for public config access
*/

// Get public tenant config for current host - /tenant-info/public
Route::get('/public', TenantPublicConfig::class)->name('tenant.public');
