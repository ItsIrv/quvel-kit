<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Actions\GetUserSessionAction;
use Modules\Auth\Actions\LoginUserAction;
use Modules\Auth\Actions\UserLogoutAction;

/*
 *--------------------------------------------------------------------------
 * Auth Routes
 *--------------------------------------------------------------------------
 *
 * All authentication related routes are defined here.
 *
 */

Route::group([
    'prefix' => 'auth',
], function (): void {
    // Login
    Route::post('/login', LoginUserAction::class)->name('auth.login');

    // Authenticated
    Route::middleware(['auth'])->group(function (): void {
        // Session Status Check
        Route::get('/session', GetUserSessionAction::class)->name('auth.session');
        // Logout
        Route::post('/logout', UserLogoutAction::class)->name('auth.logout');
    });
});
