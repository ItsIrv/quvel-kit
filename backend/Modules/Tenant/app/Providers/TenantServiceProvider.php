<?php

namespace Modules\Tenant\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Support\Facades\Route;
use Modules\Tenant\Console\ManageTenantConfig;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Http\Middleware\TenantMiddleware;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\Services\TenantResolverService;
use Modules\Tenant\Services\TenantSessionService;

/**
 * Provider for the Tenant module.
 */
class TenantServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tenant';

    protected string $nameLower = 'tenant';

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(TenantSessionService::class);
        $this->app->singleton(TenantFindService::class);
        $this->app->singleton(TenantResolverService::class);

        $this->app->scoped(TenantContext::class);
    }

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        parent::boot();

        $this->registerMiddleware();

        // If running in CLI, register the command
        if ($this->app->runningInConsole()) {
            $this->commands([
                ManageTenantConfig::class,
            ]);
        }
    }

    /**
     * Register the middleware.
     */
    public function registerMiddleware(): void
    {
        Route::aliasMiddleware('tenant', TenantMiddleware::class);
    }
}
