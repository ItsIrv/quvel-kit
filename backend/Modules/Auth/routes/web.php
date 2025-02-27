<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Actions\GetUserSessionAction;
use Modules\Auth\Actions\LoginUserAction;
use Modules\Auth\Actions\UserLogoutAction;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the 'web' middleware group. Now create something great!
|
*/

Route::group([
    'prefix' => 'auth',
], function (): void {
    // Login
    Route::post('/login', LoginUserAction::class)->name('auth.login');

    // Authenticated
    Route::middleware(['auth'])->group(function (): void {
        Route::get('/session', GetUserSessionAction::class)->name('auth.session');
        Route::post('/logout', UserLogoutAction::class)->name('auth.logout');
    });
});
