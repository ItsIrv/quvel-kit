<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Actions\Debug\ShowProxyInfoAction;

Route::get('/debug/proxy-info', ShowProxyInfoAction::class)->name('debug.proxy-info');
