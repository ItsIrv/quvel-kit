<?php

namespace Modules\TenantAdmin\Providers;

use Modules\Core\Providers\ModuleServiceProvider;
use Modules\TenantAdmin\Http\Middleware\CheckInstalled;
use Modules\TenantAdmin\Http\Middleware\CheckNotInstalled;
use Modules\TenantAdmin\Http\Middleware\TenantAdminAuth;

class TenantAdminServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'TenantAdmin';

    protected string $nameLower = 'tenantadmin';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Boot any application services.
     */
    public function boot(): void
    {
        parent::boot();

        // Register middleware aliases
        $this->registerMiddleware();
    }

    /**
     * Register middleware aliases.
     */
    protected function registerMiddleware(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('check_installed', CheckInstalled::class);
        $router->aliasMiddleware('check_not_installed', CheckNotInstalled::class);
        $router->aliasMiddleware('tenant_admin_auth', TenantAdminAuth::class);
    }
}
