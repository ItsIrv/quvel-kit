<?php

namespace Modules\TenantAdmin\Providers;

use Modules\Core\Providers\ModuleRouteServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ModuleRouteServiceProvider
{
    protected string $name = 'TenantAdmin';

    /**
     * Define the "web" routes for the application.
     */
    protected function mapWebRoutes(): void
    {
        $webRoutes = module_path($this->name, '/routes/web.php');

        if (file_exists($webRoutes)) {
            Route::middleware('web')
                ->prefix('tenant/admin')
                ->name('tenant.admin.')
                ->group($webRoutes);
        }
    }

    /**
     * Define the "api" routes for the application.
     */
    protected function mapApiRoutes(): void
    {
        $apiRoutes = module_path($this->name, '/routes/api.php');

        if (file_exists($apiRoutes)) {
            Route::middleware('api')
                ->prefix('api/tenant/admin')
                ->name('api.tenant.admin.')
                ->group($apiRoutes);
        }
    }
}
