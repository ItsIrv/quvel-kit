<?php

namespace Modules\Tenant\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register and configure tenant routes with custom middleware groups.
     */
    public function boot(): void
    {
        parent::boot();

        $this->routes(function () {
            $this->registerTenantRoutes();
        });
    }

    /**
     * Register tenant routes with configurable prefix and middleware.
     */
    protected function registerTenantRoutes(): void
    {
        $prefix        = config('tenant.endpoints.prefix', '/tenant-info');
        $internalGroup = config('tenant.middleware.internal_group', 'tenant_internal');
        $publicGroup   = config('tenant.middleware.public_group', 'tenant_public');

        // Register internal tenant routes
        Route::middleware($internalGroup)
            ->prefix($prefix)
            ->group(function () {
                $this->loadRoutesFrom(module_path('Tenant', 'routes/internal.php'));
            });

        // Register public tenant routes
        Route::middleware($publicGroup)
            ->prefix($prefix)
            ->group(function () {
                $this->loadRoutesFrom(module_path('Tenant', 'routes/public.php'));
            });

        $channelRoutes = module_path('Tenant', 'routes/channels.php');

        require $channelRoutes;
    }
}
