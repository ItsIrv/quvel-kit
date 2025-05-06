<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Actions\Email\VerificationNotice;
use Modules\Auth\Actions\Socialite\CallbackAction;
use Modules\Auth\Actions\Socialite\CreateClientNonceAction;
use Modules\Auth\Actions\Socialite\RedeemClientNonceAction;
use Modules\Auth\Actions\Socialite\RedirectAction;
use Modules\Auth\Actions\User\GetSessionAction;
use Modules\Auth\Actions\User\LoginAction;
use Modules\Auth\Actions\User\LogoutAction;
use Modules\Auth\Actions\User\RegisterAction;
use Modules\Auth\Actions\Email\VerificationVerify;
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
    Route::post('/login', LoginAction::class)
        ->middleware('throttle:login')
        ->name('login');

    Route::post('/register', RegisterAction::class)
        ->middleware('throttle:register')
        ->name('register');

    Route::post('/email/verification-notification', VerificationNotice::class)
        ->middleware('throttle:verification.notice')
        ->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', VerificationVerify::class)
        ->middleware('signed')
        ->name('verification.verify');

    // Socialite
    Route::group([
        'prefix'     => 'provider/{provider}',
        'middleware' => ConfigGate::class . ':auth.disable_socialite,false',
    ], static function (): void {
        Route::get('/redirect', RedirectAction::class)
            ->middleware('throttle:provider.redirect')
            ->name('provider.redirect');

        Route::get('/callback', CallbackAction::class)
            ->middleware('throttle:provider.callback')
            ->name('provider.callback');

        Route::post('/callback', CallbackAction::class)
            ->middleware('throttle:provider.callback.post')
            ->name('provider.callback.post');

        Route::post('/create-nonce', CreateClientNonceAction::class)
            ->middleware('throttle:provider.create-nonce')
            ->name('provider.create-nonce');

        Route::post('/redeem-nonce', RedeemClientNonceAction::class)
            ->middleware('throttle:provider.redeem-nonce')
            ->name('provider.redeem-nonce');
    });

    // Authenticated
    Route::middleware(['auth'])->group(static function (): void {
        // Session Status Check
        Route::get('/session', GetSessionAction::class)->name('session');
        // Logout
        Route::post('/logout', LogoutAction::class)->name('logout');
    });
});
