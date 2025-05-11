<?php

namespace Modules\Tenant\Providers;

use Modules\Core\Providers\ModuleServiceProvider;
use Exception;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Log;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Services\TenantConfigApplier;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\Services\RequestPrivacyService;
use Modules\Tenant\Services\TenantResolverService;
use Modules\Tenant\Services\TenantSessionService;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Http\Middleware\TenantMiddleware;

/**
 * Provider for the Tenant module.
 */
class TenantServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Tenant';

    protected string $nameLower = 'tenant';

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(TenantFindService::class);

        $this->app->scoped(TenantResolverService::class);
        $this->app->scoped(TenantSessionService::class);
        $this->app->scoped(TenantContext::class);
        $this->app->scoped(RequestPrivacyService::class);

        $this->app->rebinding('request', function (Application $app): void {
            try {
                $tenantContext = $app->make(TenantContext::class);
                $tenant        = $tenantContext->get();

                TenantConfigApplier::apply(
                    $tenant,
                    $app->make(ConfigRepository::class),
                );
            } catch (Exception $e) {
                Log::critical('Tenant Config Could Not Be Applied: ' . $e->getMessage());
            }
        });
    }

    public function boot(): void
    {
        parent::boot();

        $this->callAfterResolving(Router::class, function (Router $router) {
            $router->prependMiddlewareToGroup('web', TenantMiddleware::class);
        });
    }
}
