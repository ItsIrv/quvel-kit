<?php

use Illuminate\Support\Facades\Route;
use Modules\Notifications\Http\Controllers\NotificationsController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group([
    'middleware' => ['auth'],
], static function () {
    Route::get('notifications', [NotificationsController::class, 'listNotifications'])
        ->name('notifications.listNotifications');

    Route::post('notifications/mark-all-read', [NotificationsController::class, 'markAllAsRead'])
        ->name('notifications.markAllAsRead');
});
