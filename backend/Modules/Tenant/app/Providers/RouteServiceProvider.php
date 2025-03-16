<?php

namespace Modules\Tenant\Providers;

use App\Providers\ModuleRouteServiceProvider;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ModuleRouteServiceProvider
{
    protected string $name = 'Tenant';
}
