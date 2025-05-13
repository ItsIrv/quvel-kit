<?php

namespace Modules\Tenant\Providers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Context;
use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Services\RequestPrivacy;
use Modules\Tenant\Services\ConfigApplier;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\Services\ResolverService;
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
        $this->app->scoped(ResolverService::class);
        $this->app->scoped(TenantContext::class);
        $this->app->scoped(RequestPrivacy::class);
    }

    /**
     * Bootstrap services.
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
}
