<?php

namespace Modules\Core\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

abstract class ModuleRouteServiceProvider extends ServiceProvider
{
    /**
     * Name of the module (used for route file resolution).
     */
    protected string $name;

    /**
     * Define the routes for the application.
     */
    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
        $this->mapChannelRoutes();
    }

    /**
     * Define the "web" routes for the application.
     */
    protected function mapWebRoutes(): void
    {
        $webRoutes = module_path($this->name, '/routes/web.php');

        if (file_exists($webRoutes)) {
            Route::middleware('web')
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
                ->prefix('api')
                ->name('api.')
                ->group($apiRoutes);
        }
    }

    /**
     * Define the broadcast channels for the application.
     */
    protected function mapChannelRoutes(): void
    {
        $channelRoutes = module_path($this->name, '/routes/channels.php');

        if (file_exists($channelRoutes)) {
            require $channelRoutes;
        }
    }
}
