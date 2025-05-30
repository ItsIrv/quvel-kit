<?php

namespace Modules\TenantAdmin\Providers;

use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Tenant\Providers\TenantServiceProvider;

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

        // Register TenantAdmin routes to be excluded from tenant resolution
        $this->registerTenantExclusions();
    }

    /**
     * Register paths that should be excluded from tenant resolution.
     */
    protected function registerTenantExclusions(): void
    {
        // Check if Tenant module is available
        if (!class_exists(TenantServiceProvider::class)) {
            return;
        }

        // Register admin routes to bypass tenant resolution
        TenantServiceProvider::excludePatterns([
            'admin/tenants*',
            'api/admin/tenants*',
        ]);
    }
}
