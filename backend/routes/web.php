<?php

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('_', function (): RedirectResponse|string {
    if (app()->environment('production')) {
        return redirect('https://quvel.127.0.0.1.nip.io');
    }

    return 'You are here because you are not logged in. Production will redirect you to the frontend.';
})->name('login');
