<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Actions\Fortify\VerificationNotification;
use Modules\Auth\Actions\Socialite\CallbackAction;
use Modules\Auth\Actions\Socialite\CreateClientNonceAction;
use Modules\Auth\Actions\Socialite\RedeemClientNonceAction;
use Modules\Auth\Actions\Socialite\RedirectAction;
use Modules\Auth\Actions\User\GetSessionAction;
use Modules\Auth\Actions\User\LoginAction;
use Modules\Auth\Actions\User\LogoutAction;
use Modules\Auth\Actions\User\RegisterAction;
use Modules\Auth\Actions\Fortify\VerificationVerify;
use Modules\Auth\Actions\Fortify\ForgotPassword;
use Modules\Auth\Actions\Fortify\PasswordViewRedirect;
use Modules\Auth\Actions\Fortify\LoginViewRedirect;
use Modules\Core\Http\Middleware\Config\CheckValue;
use Modules\Core\Http\Middleware\Security\VerifyCaptcha;

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
    // Fortify Overrides
    Route::group([
        'middleware' => 'guest',
    ], static function (): void {
        Route::post('/login', LoginAction::class)
            ->middleware('throttle:login')
            ->name('login.process');

        Route::get('/login', LoginViewRedirect::class)
            ->name('login');

        Route::post('/register', RegisterAction::class)
            ->middleware([
                'throttle:register',
                VerifyCaptcha::class,
            ])
            ->name('register');

        Route::post('/email/verification-notification', VerificationNotification::class)
            ->middleware('throttle:verification.notice')
            ->name('verification.notice');

        Route::get('/email/verify/{id}/{hash}', VerificationVerify::class)
            ->middleware('signed')
            ->name('verification.verify');

        Route::post('/forgot-password', ForgotPassword::class)
            ->middleware([
                'throttle:login',
                VerifyCaptcha::class,
            ])
            ->name('forgot-password');

        Route::get('/password/{token}', PasswordViewRedirect::class)
            ->name('password.reset');
    });

    // Socialite
    Route::group([
        'prefix'     => 'provider/{provider}',
        'middleware' => [
            'guest',
            CheckValue::class . ':auth.disable_socialite,false',
        ],
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
    Route::group([
        'middleware' => 'auth',
    ], static function (): void {
        // Session Status Check
        Route::get('/session', GetSessionAction::class)->name('session');
        // Logout
        Route::post('/logout', LogoutAction::class)->name('logout');
    });
});
