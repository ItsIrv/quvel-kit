<?php

namespace Modules\Tenant\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Context;
use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Services\RequestPrivacy;
use Modules\Tenant\Services\ConfigApplier;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\Services\TenantConfigProviderRegistry;
use Modules\Tenant\Services\FindService;
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
     * @param string|\Modules\Tenant\Contracts\TenantConfigProviderInterface $provider
     * @return void
     */
    public static function registerConfigProvider(string|\Modules\Tenant\Contracts\TenantConfigProviderInterface $provider): void
    {
        app(TenantConfigProviderRegistry::class)->register($provider);
    }
}
