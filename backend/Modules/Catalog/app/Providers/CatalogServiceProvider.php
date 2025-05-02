<?php

namespace Modules\Catalog\Providers;

use Modules\Core\Providers\ModuleServiceProvider;

class CatalogServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Catalog';

    protected string $nameLower = 'catalog';

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
}
