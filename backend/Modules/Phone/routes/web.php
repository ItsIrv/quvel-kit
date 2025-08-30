<?php

use Illuminate\Support\Facades\Route;
use Modules\Phone\Http\Controllers\PhoneController;

/*
|--------------------------------------------------------------------------
| Phone Routes
|--------------------------------------------------------------------------
|
| Here are the routes for phone number management and verification.
| All routes are protected by authentication middleware.
|
*/

Route::middleware(['auth'])->prefix('phone')->name('phone.')->group(function (): void {
    Route::post('/send-verification', [PhoneController::class, 'sendVerification'])
        ->name('send-verification');
    Route::post('/verify', [PhoneController::class, 'verify'])
        ->name('verify');
    Route::delete('/', [PhoneController::class, 'remove'])
        ->name('remove');
});
