<?php

namespace Modules\Tenant\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Context;
use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Contracts\TenantConfigProviderInterface;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Services\RequestPrivacy;
use Modules\Tenant\Services\ConfigApplier;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\Services\TenantConfigProviderRegistry;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\Services\TierService;
use Modules\Tenant\Services\TenantTableRegistry;
use Illuminate\Log\Context\Repository;

/**
 * Provider for the Tenant module.
 */
class TenantServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tenant';
    protected string $nameLower = 'tenant';

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(FindService::class);
        $this->app->singleton(TierService::class);

        // Register the tenant table registry
        $this->app->singleton(TenantTableRegistry::class, function ($app) {
            $registry = new TenantTableRegistry();

            // Load tables from config first
            $configTables = config('tenant.tables', []);
            $registry->registerTables($configTables);

            return $registry;
        });

        // Register the configuration pipeline
        $this->app->singleton(ConfigurationPipeline::class, function ($app) {
            $pipeline = new ConfigurationPipeline();

            // Register pipes from config
            $pipes = config('tenant.config_pipes', []);
            $pipeline->registerMany($pipes);

            return $pipeline;
        });

        // Register the tenant config provider registry
        $this->app->singleton(TenantConfigProviderRegistry::class);

        $this->app->scoped(TenantContext::class);
        $this->app->scoped(RequestPrivacy::class);
        $this->app->scoped(
            TenantResolver::class,
            fn (): TenantResolver => app(config('tenant.resolver'))
        );
    }

    /**
     * Bootstraps services and manage the logging context for tenant.
     */
    public function boot(): void
    {
        parent::boot();

        $this->registerTenantTables(config('tenant.tables', []));

        Context::dehydrating(function (Repository $context): void {
            $context->addHidden('tenant', app(TenantContext::class)->get());
        });

        Context::hydrated(function (Repository $context): void {
            if ($context->hasHidden('tenant')) {
                ConfigApplier::apply(
                    $context->getHidden('tenant'),
                    $this->app->make(ConfigRepository::class),
                );
            }
        });
    }

    /**
     * Register a configuration pipe.
     * This method allows other modules to register their own configuration pipes.
     *
     * @param string|ConfigurationPipeInterface $pipe
     * @return void
     */
    public static function registerConfigPipe(string|ConfigurationPipeInterface $pipe): void
    {
        app(ConfigurationPipeline::class)->register($pipe);
    }

    /**
     * Register a tenant config provider.
     * This method allows other modules to add configuration to tenant API responses.
     *
     * @param string|TenantConfigProviderInterface $provider
     * @return void
     */
    public static function registerConfigProvider(string|TenantConfigProviderInterface $provider): void
    {
        app(TenantConfigProviderRegistry::class)->register($provider);
    }

    /**
     * Register a tenant-aware table.
     * This method allows other modules to register tables that need tenant isolation.
     *
     * @param string $tableName
     * @param array $config Optional configuration for the table
     * @return void
     */
    public static function registerTenantTable(string $tableName, array $config = []): void
    {
        app(TenantTableRegistry::class)->registerTable($tableName, $config);
    }

    /**
     * Register multiple tenant-aware tables.
     * This method allows other modules to register multiple tables at once.
     *
     * @param array $tables Array of table names or table => config pairs
     * @return void
     */
    public static function registerTenantTables(array $tables): void
    {
        app(TenantTableRegistry::class)->registerTables($tables);
    }
}
