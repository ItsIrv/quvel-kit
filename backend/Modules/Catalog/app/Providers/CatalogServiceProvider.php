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

        if (class_exists(TenantServiceProvider::class)) {
            $this->app->booted(function (): void {
                TenantServiceProvider::registerTenantTable(
                    'catalog_items',
                    [
                        'cascade_delete' => true,
                    ],
                );
            });
        }
    }
}
