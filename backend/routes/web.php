<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function (): mixed {
    if (app()->environment('local')) {
        return view('welcome');
    }

    return redirect()->to(env('VITE_APP_URL'));
})->name('welcome');
