<?php

namespace Modules\Auth\Providers;

use Modules\Auth\Actions\Fortify\CreateNewUser;
use Modules\Auth\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
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
        // Disable the default login/register routes since they're already implemented in Auth module
        Fortify::ignoreRoutes();

        // We're not using Fortify for user creation as we have our own implementation
        // but we still need to register the action for other Fortify features
        Fortify::createUsersUsing(CreateNewUser::class);

        // Enable profile management features
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        // Configure rate limiting
        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Register custom Fortify routes for password reset and profile management
        // $this->registerFortifyRoutes();
    }

    /**
     * Register the routes needed for password reset and profile management.
     *
     * This method only registers the Fortify routes we want to use, excluding
     * login and registration which are already implemented in the Auth module.
     */
    private function registerFortifyRoutes(): void
    {
        Route::group(['middleware' => config('fortify.middleware', ['web'])], function () {
            // Password Reset Routes
            $this->configurePasswordResetRoutes();

            // User Profile Routes
            $this->configureProfileRoutes();

            // Two Factor Authentication
            $this->configureTwoFactorRoutes();
        });
    }

    /**
     * Configure the password reset routes.
     */
    private function configurePasswordResetRoutes(): void
    {
        $passwordResetController = \Laravel\Fortify\Http\Controllers\PasswordResetLinkController::class;
        $newPasswordController   = \Laravel\Fortify\Http\Controllers\NewPasswordController::class;

        Route::get('/forgot-password', [$passwordResetController, 'create'])
            ->middleware(['guest'])
            ->name('password.request');

        Route::post('/forgot-password', [$passwordResetController, 'store'])
            ->middleware(['guest'])
            ->name('password.email');

        Route::get('/reset-password/{token}', [$newPasswordController, 'create'])
            ->middleware(['guest'])
            ->name('password.reset');

        Route::post('/reset-password', [$newPasswordController, 'store'])
            ->middleware(['guest'])
            ->name('password.update');
    }

    /**
     * Configure the user profile routes.
     */
    private function configureProfileRoutes(): void
    {
        $profileController  = \Laravel\Fortify\Http\Controllers\ProfileInformationController::class;
        $passwordController = \Laravel\Fortify\Http\Controllers\PasswordController::class;

        Route::get('/user/profile', [$profileController, 'show'])
            ->middleware(['auth'])
            ->name('profile.show');

        Route::put('/user/profile-information', [$profileController, 'update'])
            ->middleware(['auth'])
            ->name('user-profile-information.update');

        Route::put('/user/password', [$passwordController, 'update'])
            ->middleware(['auth'])
            ->name('user-password.update');
    }

    /**
     * Configure the two-factor authentication routes.
     */
    private function configureTwoFactorRoutes(): void
    {
        $twoFactorController    = \Laravel\Fortify\Http\Controllers\TwoFactorAuthenticationController::class;
        $confirmedController    = \Laravel\Fortify\Http\Controllers\ConfirmedTwoFactorAuthenticationController::class;
        $qrCodeController       = \Laravel\Fortify\Http\Controllers\TwoFactorQrCodeController::class;
        $recoveryCodeController = \Laravel\Fortify\Http\Controllers\RecoveryCodeController::class;

        Route::get('/user/two-factor-authentication', [$twoFactorController, 'show'])
            ->middleware(['auth'])
            ->name('two-factor.show');

        Route::post('/user/two-factor-authentication', [$twoFactorController, 'store'])
            ->middleware(['auth'])
            ->name('two-factor.enable');

        Route::delete('/user/two-factor-authentication', [$twoFactorController, 'destroy'])
            ->middleware(['auth'])
            ->name('two-factor.disable');

        Route::get('/user/confirmed-two-factor-authentication', [$confirmedController, 'show'])
            ->middleware(['auth'])
            ->name('two-factor.confirm');

        Route::post('/user/confirmed-two-factor-authentication', [$confirmedController, 'store'])
            ->middleware(['auth'])
            ->name('two-factor.confirm');

        Route::get('/user/two-factor-qr-code', [$qrCodeController, 'show'])
            ->middleware(['auth', 'password.confirm'])
            ->name('two-factor.qr-code');

        Route::get('/user/two-factor-recovery-codes', [$recoveryCodeController, 'index'])
            ->middleware(['auth', 'password.confirm'])
            ->name('two-factor.recovery-codes');

        Route::post('/user/two-factor-recovery-codes', [$recoveryCodeController, 'store'])
            ->middleware(['auth', 'password.confirm'])
            ->name('two-factor.regenerate');
    }
}
