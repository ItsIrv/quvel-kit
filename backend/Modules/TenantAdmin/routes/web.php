<?php

use Illuminate\Support\Facades\Route;
use Modules\TenantAdmin\Http\Controllers\PageController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your module. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Catch-all route for SPA
Route::get('/{any}', [PageController::class, 'show'])
    ->where('any', '.*')
    ->name('spa');
