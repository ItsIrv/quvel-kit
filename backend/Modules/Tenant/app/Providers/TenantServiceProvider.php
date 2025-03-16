<?php

namespace Modules\Tenant\Providers;

use App\Providers\ModuleServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Http\Middleware\TenantMiddleware;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\Services\TenantResolverService;
use Modules\Tenant\Services\TenantSessionService;

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
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(TenantSessionService::class);
        $this->app->singleton(TenantFindService::class);
        $this->app->singleton(TenantResolverService::class);

        $this->app->scoped(TenantContext::class);

        $this->bindTenantConfigs();
    }

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        parent::boot();

        $this->bootMiddleware();
    }

    /**
     * Dynamically set environment variables per tenant.
     */
    private function bindTenantConfigs(): void
    {
        $this->app->rebinding('request', function (Application $app): void {
            try {
                $tenantContext = $app->make(TenantContext::class);
                $tenant        = $tenantContext->get();
                $tenantConfig  = $tenant->getEffectiveConfig();

                if (!$tenantConfig) {
                    throw new \Exception("Tenant config not found");
                }

                $appConfig = $app['config'];

                // Backend Configuration (API)
                $appConfig->set('app.name', $tenantConfig->appName);
                $appConfig->set('app.env', $tenantConfig->appEnv);
                $appConfig->set('app.debug', $tenantConfig->debug);
                $appConfig->set('app.url', $tenantConfig->apiUrl);

                // Frontend Configuration (appUrl)
                $appConfig->set('vite.api_url', $tenantConfig->apiUrl);
                $appConfig->set('vite.app_url', $tenantConfig->appUrl);

                // Email Branding
                $appConfig->set('mail.from.name', $tenantConfig->mailFromName);
                $appConfig->set('mail.from.address', $tenantConfig->mailFromAddress);

                // OAuth Config (Google Login)
                $appConfig->set(
                    'services.google.redirect',
                    "{$tenantConfig->apiUrl}/auth/provider/google/callback",
                );

                // Ensure session domain is properly scoped to the backend
                $apiHost = parse_url(
                    $tenantConfig->apiUrl,
                    PHP_URL_HOST,
                );

                if ($apiHost) {
                    $parts = explode('.', $apiHost);
                    if (count($parts) > 2) {
                        array_shift($parts);
                    }
                    $sessionDomain = '.' . implode('.', $parts);
                    $appConfig->set('session.domain', $sessionDomain);
                }
            } catch (\Exception $e) {
                \Log::critical("Tenant Config Could Not Be Applied: " . $e->getMessage());
            }
        });
    }

    /**
     * Register the middleware.
     */
    public function bootMiddleware(): void
    {
        Route::aliasMiddleware('tenant', TenantMiddleware::class);
    }
}
