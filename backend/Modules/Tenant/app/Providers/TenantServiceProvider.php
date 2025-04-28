<?php

namespace Modules\Tenant\Providers;

use App\Providers\ModuleServiceProvider;
use Exception;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Http\Middleware\TenantMiddleware;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\Services\TenantResolverService;
use Modules\Tenant\Services\TenantSessionService;
use RuntimeException;

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
        /** @phpstan-ignore-next-line */
        $this->app->rebinding('request', function (Application $app): void {
            try {
                $tenantContext = $app->make(TenantContext::class);
                $tenant        = $tenantContext->get();
                $tenantConfig  = $tenant->getEffectiveConfig();

                if (!$tenantConfig) {
                    throw new RuntimeException('Tenant config not found');
                }

                /** @var Repository $appConfig */
                $appConfig = $app['config'];

                // Backend Configuration
                $appConfig->set('app.name', $tenantConfig->appName);
                $appConfig->set('app.env', $tenantConfig->appEnv);
                $appConfig->set('app.debug', $tenantConfig->debug);
                $appConfig->set('app.url', $tenantConfig->apiUrl);

                // Frontend Configuration
                $appConfig->set('vite.api_url', $tenantConfig->apiUrl);
                $appConfig->set('vite.app_url', $tenantConfig->appUrl);

                // Email Branding
                $appConfig->set('mail.from.name', $tenantConfig->mailFromName);
                $appConfig->set('mail.from.address', $tenantConfig->mailFromAddress);

                // OAuth Config
                $appConfig->set(
                    'services.google.redirect',
                    "$tenantConfig->apiUrl/auth/provider/google/callback",
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
            } catch (Exception $e) {
                Log::critical('Tenant Config Could Not Be Applied: ' . $e->getMessage());
            }
        });
    }

    /**
     * Register the middleware.
     */
    public function bootMiddleware(): void
    {
        $this->app['router']->pushMiddlewareToGroup('web', TenantMiddleware::class);
        $this->app['router']->pushMiddlewareToGroup('api', TenantMiddleware::class);
    }
}
require module_path('Tenant', 'app/helpers.php');
