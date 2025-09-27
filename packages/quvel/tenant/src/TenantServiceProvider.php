<?php

declare(strict_types=1);

namespace Quvel\Tenant;

use Illuminate\Support\ServiceProvider;

class TenantServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/tenant.php',
            'tenant'
        );

        $this->app->singleton(TenantConfigManager::class, function ($app) {
            $handlers = config('tenant.config_handlers', []);
            $manager = new TenantConfigManager();
            $manager->registerHandlers($handlers);

            return $manager;
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/tenant.php' => config_path('tenant.php'),
            ], 'tenant-config');

            $this->publishes([
                __DIR__.'/../database/migrations/' => database_path('migrations'),
            ], 'tenant-migrations');

            $this->commands([
                Commands\TenantInstallCommand::class,
            ]);
        }
    }
}