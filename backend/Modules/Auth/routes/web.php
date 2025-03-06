<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Modules\Auth\Actions\Socialite\CallbackAction;
use Modules\Auth\Actions\Socialite\RedirectAction;
use Modules\Auth\Actions\User\GetUserSessionAction;
use Modules\Auth\Actions\User\LoginUserAction;
use Modules\Auth\Actions\User\RegisterUserAction;
use Modules\Auth\Actions\User\UserLogoutAction;

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
    // Register
    Route::post('/register', RegisterUserAction::class)->name('auth.register');
    // Socialite
    Route::group([
        'prefix' => 'provider/{provider}',
    ], function (): void {
        Route::get('/redirect', RedirectAction::class)->name('auth.provider.redirect');
        Route::get('/callback', CallbackAction::class)->name('auth.provider.callback');
    });

    // Authenticated
    Route::middleware(['auth'])->group(function (): void {
        // Session Status Check
        Route::get('/session', GetUserSessionAction::class)->name('auth.session');
        // Logout
        Route::post('/logout', UserLogoutAction::class)->name('auth.logout');
    });
});
