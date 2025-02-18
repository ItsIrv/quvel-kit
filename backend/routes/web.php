<?php

use App\Actions\QuvelWelcome;
use Illuminate\Support\Facades\Route;

Route::get('/', QuvelWelcome::class)->name('welcome');
