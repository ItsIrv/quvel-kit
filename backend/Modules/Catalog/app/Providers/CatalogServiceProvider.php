<?php

namespace Modules\Catalog\Providers;

use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Tenant\Providers\TenantServiceProvider;

class CatalogServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Catalog';

    protected string $nameLower = 'catalog';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        parent::boot();

        // Register catalog_items as a tenant-aware table
        TenantServiceProvider::registerTenantTable('catalog_items', [
            'cascade_delete' => true,
        ]);
    }
}
