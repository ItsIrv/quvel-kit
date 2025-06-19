<?php

namespace Modules\Tenant\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\Services\TenantMemoryCache;
use Modules\Tenant\Services\TenantExclusionRegistry;
use Modules\Tenant\Services\TenantModuleConfigLoader;
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

        // Register core services
        $this->app->singleton(TenantModuleConfigLoader::class);
        $this->app->singleton(FindService::class);
        $this->app->singleton(TenantExclusionRegistry::class);
        $this->app->singleton(TenantMemoryCache::class);

        // Register the configuration pipeline with module pipes
        $this->app->singleton(ConfigurationPipeline::class, function ($app): ConfigurationPipeline {
            $pipeline = new ConfigurationPipeline();
            $loader   = $app->make(TenantModuleConfigLoader::class);

            // Load pipes from all modules
            $pipes = $loader->getAllPipes();
            $pipeline->registerMany($pipes);

            return $pipeline;
        });

        $this->app->scoped(TenantContext::class);
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

        // Load exclusions from all modules
        $loader            = $this->app->make(TenantModuleConfigLoader::class);
        $exclusionRegistry = $this->app->make(TenantExclusionRegistry::class);

        // Register exclusion paths from all modules
        $exclusionPaths = $loader->getAllExclusionPaths();
        if ($exclusionPaths !== []) {
            $exclusionRegistry->excludePaths($exclusionPaths);
        }

        // Register exclusion patterns from all modules
        $exclusionPatterns = $loader->getAllExclusionPatterns();
        if ($exclusionPatterns !== []) {
            $exclusionRegistry->excludePatterns($exclusionPatterns);
        }

        $this->registerTenantMiddlewareGroups();

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
     * Register custom middleware groups for tenant endpoints.
     */
    protected function registerTenantMiddlewareGroups(): void
    {
        $middlewareGroups = config('tenant.middleware.groups', []);

        foreach ($middlewareGroups as $groupName => $groupMiddleware) {
            Route::middlewareGroup($groupName, $groupMiddleware);
        }
    }
}
