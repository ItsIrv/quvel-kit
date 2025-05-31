<?php

use Illuminate\Support\Facades\Route;
use Modules\TenantAdmin\Http\Controllers\PageController;
use Modules\TenantAdmin\Http\Controllers\Api\InstallationController;
use Modules\TenantAdmin\Http\Controllers\AuthController;

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

// Installation routes (only accessible when not installed)
Route::middleware(['check_not_installed'])->group(function () {
    Route::get('/install', [PageController::class, 'show'])->name('install');
    Route::get('/install/status', [InstallationController::class, 'status'])->name('api.install.status');
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
    });
});

// Catch-all route for SPA
Route::get('/{any}', [PageController::class, 'show'])
    ->where('any', '.*')
    ->name('spa');
