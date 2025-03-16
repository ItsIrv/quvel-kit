<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Actions\Socialite\CallbackAction;
use Modules\Auth\Actions\Socialite\CreateClientNonceAction;
use Modules\Auth\Actions\Socialite\RedeemClientNonceAction;
use Modules\Auth\Actions\Socialite\RedirectAction;
use Modules\Auth\Actions\User\GetSessionAction;
use Modules\Auth\Actions\User\LoginAction;
use Modules\Auth\Actions\User\LogoutAction;
use Modules\Auth\Actions\User\RegisterAction;

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
], static function (): void {
    // Login
    Route::post('/login', LoginAction::class)->name('auth.login');
    // Register
    Route::post('/register', RegisterAction::class)->name('auth.register');
    // Socialite
    Route::group([
        'prefix' => 'provider/{provider}',
    ], static function (): void {
        Route::get('/redirect', RedirectAction::class)->name('auth.provider.redirect');
        Route::get('/callback', CallbackAction::class)->name('auth.provider.callback');
        Route::post('/callback', CallbackAction::class)->name('auth.provider.callback.post');
        Route::post('/create-nonce', CreateClientNonceAction::class)->name('auth.provider.create-nonce');
        Route::post('/redeem-nonce', RedeemClientNonceAction::class)->name('auth.provider.redeem-nonce');
    });

    // Authenticated
    Route::middleware(['auth'])->group(function (): void {
        // Session Status Check
        Route::get('/session', GetSessionAction::class)->name('auth.session');
        // Logout
        Route::post('/logout', LogoutAction::class)->name('auth.logout');
    });
});
