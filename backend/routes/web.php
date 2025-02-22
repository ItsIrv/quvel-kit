<?php

use App\Actions\QuvelWelcome;
use Dotenv\Exception\ValidationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;

// TODO: Move these to individual actions.
Route::get('/', QuvelWelcome::class)->name('welcome');

Route::post('/login', function (Request $request): User {
    $request->validate([
        'email'    => 'required|string|email',
        'password' => 'required|string',
    ]);

    $email    = $request->input('email');
    $password = $request->input('password');
    $user     = User::first();

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
})->middleware('auth');

Route::get('_', function (): RedirectResponse|string {
    if (app()->environment('production')) {
        return redirect('https://quvel.127.0.0.1.nip.io');
    }

    return 'You are here because you are not logged in. Production will redirect you to the frontend.';
})->name('login');
