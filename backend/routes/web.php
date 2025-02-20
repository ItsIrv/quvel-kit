<?php

use App\Actions\QuvelWelcome;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

Route::get('/', QuvelWelcome::class)->name('welcome');

Route::post('/login', function (): User {
    $user = User::first();

    Auth::loginUsingId($user->id);

    return $user;
});

Route::post('/logout', function (): string {
    Auth::logout();

    return 'ok';
});

Route::get('/session', function (): mixed {
    return Auth::user();
})->middleware('auth');

Route::get('/test', function (): Collection {
    return User::limit(5)->get();
});

Route::get('_', fn () => redirect('https://quvel.127.0.0.1.nip.io'))->name('login');
