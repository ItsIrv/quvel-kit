<?php

use Illuminate\Support\Facades\Route;
use Modules\TenantAdmin\Http\Controllers\PageController;
use Modules\TenantAdmin\Http\Controllers\InstallationController;
use Modules\TenantAdmin\Http\Controllers\AuthController;
use Modules\TenantAdmin\Http\Controllers\TenantController;
use Modules\Tenant\Http\Middleware\IsInternalRequest;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::group([
    'prefix'     => 'api',
    'middleware' => [
        IsInternalRequest::class,
    ],
], function () {
    // Installation routes (only accessible when not installed)
    Route::get('/install/status', [InstallationController::class, 'status'])->name('api.install.status');

    Route::middleware(['check_not_installed'])->group(function () {
        Route::get('/install', [PageController::class, 'show'])->name('install');
        Route::post('/install', [InstallationController::class, 'install'])->name('api.install');
    });

    // Protected routes (only accessible when installed)
    Route::middleware(['check_installed'])->group(function () {
        // Authentication routes
        Route::post('/login', [AuthController::class, 'login'])->name('login');
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

        // Authenticated routes
        Route::middleware(['tenant_admin_auth'])->group(function () {
            Route::get('/user', [AuthController::class, 'user'])->name('user');

            // Tenant management API routes
            Route::get('/tenants', [TenantController::class, 'index'])->name('api.tenants.index');
            Route::get('/tenants/search', [TenantController::class, 'search'])->name('api.tenants.search');
            Route::get('/tenants/{tenant}', [TenantController::class, 'show'])->name('api.tenants.show');
            Route::post('/tenants', [TenantController::class, 'store'])->name('api.tenants.store');
            Route::put('/tenants/{tenant}', [TenantController::class, 'update'])->name('api.tenants.update');
            Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy'])->name('api.tenants.destroy');
        });
    });
});

// Catch-all route for SPA
Route::get('/{any?}', [PageController::class, 'show'])
    ->middleware(IsInternalRequest::class)
    ->where('any', '.*')
    ->name('spa');
