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

        // Register TenantAdmin routes to be excluded from tenant resolution
        $this->registerTenantExclusions();
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

    /**
     * Register paths that should be excluded from tenant resolution.
     * This is now handled by the config/tenant.php file.
     */
    protected function registerTenantExclusions(): void
    {
        // Tenant exclusions are now managed via config/tenant.php
    }
}
