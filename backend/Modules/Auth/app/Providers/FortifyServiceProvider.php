<?php

namespace Modules\Auth\Providers;

use Modules\Auth\Actions\Fortify\PasswordReset;
use Modules\Auth\Actions\Fortify\UpdateUserPassword;
use Modules\Auth\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::ignoreRoutes();
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(PasswordReset::class);

        RateLimiter::for(
            'login',
            fn (Request $request) =>
            Limit::perMinute(5)->by(strtolower($request->input('email')) . '|' . $request->ip())
        );

        RateLimiter::for(
            'register',
            fn (Request $request) =>
            Limit::perMinute(3)->by($request->ip())
        );

        RateLimiter::for(
            'verification.notice',
            fn (Request $request) =>
            Limit::perMinutes(60, 3)->by($request->input('email') ?? $request->ip())
        );

        RateLimiter::for(
            'password.email',
            fn (Request $request) =>
            Limit::perMinutes(60, 3)->by($request->input('email') . '|' . $request->ip())
        );

        RateLimiter::for(
            'password.update',
            fn (Request $request) =>
            Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for(
            'password.confirm',
            fn (Request $request) =>
            Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for(
            'user-password.update',
            fn (Request $request) =>
            Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for(
            'user-profile-information.update',
            fn (Request $request) =>
            Limit::perMinute(3)->by($request->ip())
        );

        RateLimiter::for(
            'provider.redirect',
            fn (Request $request) =>
            Limit::perMinute(10)->by($request->ip())
        );

        RateLimiter::for(
            'provider.callback',
            fn (Request $request) =>
            Limit::perMinute(10)->by($request->ip())
        );

        RateLimiter::for(
            'provider.callback.post',
            fn (Request $request) =>
            Limit::perMinute(10)->by($request->ip())
        );

        RateLimiter::for(
            'provider.create-nonce',
            fn (Request $request) =>
            Limit::perMinute(5)->by($request->ip())
        );

        RateLimiter::for(
            'provider.redeem-nonce',
            fn (Request $request) =>
            Limit::perMinute(5)->by($request->ip())
        );
    }
}
