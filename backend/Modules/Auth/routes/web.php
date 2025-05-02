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
use Modules\Auth\Http\Controllers\EmailController;
use Modules\Core\Http\Middleware\ConfigGate;

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
    /**
     * Fortify Overwrites
     */
    Route::post('/login', LoginAction::class)->name('login.store');
    Route::post('/register', RegisterAction::class)->name('register.store');
    Route::post('/email/verification-notification', [EmailController::class, 'verificationNotice'])
        ->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailController::class, 'verificationVerify'])
        ->middleware('signed')
        ->name('verification.verify');

    // Socialite
    Route::group([
        'prefix'     => 'provider/{provider}',
        'middleware' => ConfigGate::class . ':auth.disable_socialite,false',
    ], static function (): void {
        Route::get('/redirect', RedirectAction::class)->name('auth.provider.redirect');
        Route::get('/callback', CallbackAction::class)->name('auth.provider.callback');
        Route::post('/callback', CallbackAction::class)->name('auth.provider.callback.post');
        Route::post('/create-nonce', CreateClientNonceAction::class)->name('auth.provider.create-nonce');
        Route::post('/redeem-nonce', RedeemClientNonceAction::class)->name('auth.provider.redeem-nonce');
    });

    Route::get('/test', function () {
        return 'test';
    })
        ->name('auth.test')
        ->middleware(ConfigGate::class . ':auth.disable_socialite,false');

    // Authenticated
    Route::middleware(['auth'])->group(static function (): void {
        // Session Status Check
        Route::get('/session', GetSessionAction::class)->name('auth.session');
        // Logout
        Route::post('/logout', LogoutAction::class)->name('auth.logout');
    });
});
