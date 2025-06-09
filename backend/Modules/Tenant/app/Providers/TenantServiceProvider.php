<?php

namespace Modules\Tenant\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Context;
use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Core\Services\Security\RequestPrivacy;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\Services\TenantTableRegistry;
use Modules\Tenant\Services\TenantConfigSeederRegistry;
use Modules\Tenant\Services\TenantExclusionRegistry;
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
        $this->app->singleton(TenantExclusionRegistry::class);

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

        // Register the tenant config seeder registry
        $this->app->singleton(TenantConfigSeederRegistry::class);

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
                app(ConfigurationPipeline::class)->apply(
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

    /**
     * Register a config seeder for tenant seed data.
     * This method allows modules to provide their own seed configuration.
     *
     * @param string $tier The tier to register for
     * @param callable $seeder A callable that returns config array
     * @param int $priority Lower numbers run first (default: 50)
     * @param callable|null $visibilitySeeder Optional callable that returns visibility array
     * @return void
     */
    public static function registerConfigSeeder(
        string $tier,
        callable $seeder,
        int $priority = 50,
        ?callable $visibilitySeeder = null,
    ): void {
        app(TenantConfigSeederRegistry::class)->registerSeeder($tier, $seeder, $priority, $visibilitySeeder);
    }

    /**
     * Register a config seeder for all tiers.
     *
     * @param callable $seeder A callable that returns config array
     * @param int $priority Lower numbers run first (default: 50)
     * @param callable|null $visibilitySeeder Optional callable that returns visibility array
     * @return void
     */
    public static function registerConfigSeederForAllTiers(
        callable $seeder,
        int $priority = 50,
        ?callable $visibilitySeeder = null,
    ): void {
        app(TenantConfigSeederRegistry::class)->registerSeederForAllTiers($seeder, $priority, $visibilitySeeder);
    }

    /**
     * Register a config seeder for multiple tiers.
     *
     * @param array $tiers Array of tier names
     * @param callable $seeder A callable that returns config array
     * @param int $priority Lower numbers run first (default: 50)
     * @param callable|null $visibilitySeeder Optional callable that returns visibility array
     * @return void
     */
    public static function registerConfigSeederForTiers(
        array $tiers,
        callable $seeder,
        int $priority = 50,
        ?callable $visibilitySeeder = null,
    ): void {
        app(TenantConfigSeederRegistry::class)->registerSeederForTiers($tiers, $seeder, $priority, $visibilitySeeder);
    }

    /**
     * Register paths to exclude from tenant resolution.
     *
     * @param string|array $paths Exact paths to exclude
     * @return void
     */
    public static function excludePaths(string|array $paths): void
    {
        app(TenantExclusionRegistry::class)->excludePaths($paths);
    }

    /**
     * Register path patterns to exclude from tenant resolution.
     *
     * @param string|array $patterns Path patterns to exclude (supports wildcards)
     * @return void
     */
    public static function excludePatterns(string|array $patterns): void
    {
        app(TenantExclusionRegistry::class)->excludePatterns($patterns);
    }
}
