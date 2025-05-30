<?php

namespace Modules\TenantAdmin\Providers;

use Modules\Core\Providers\ModuleServiceProvider;

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
        // Merge additional excluded patterns into the tenant config
        $currentPatterns = config('tenant.excluded_patterns', []);
        
        $adminPatterns = [
            'admin/tenants*',
            'api/admin/tenants*',
        ];

        config([
            'tenant.excluded_patterns' => array_unique(array_merge($currentPatterns, $adminPatterns))
        ]);
    }
}
