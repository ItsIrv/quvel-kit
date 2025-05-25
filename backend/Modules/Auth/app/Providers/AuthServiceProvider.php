<?php

namespace Modules\Auth\Providers;

use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\HmacService;
use Modules\Auth\Services\NonceSessionService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;
use Modules\Auth\Services\UserAuthenticationService;

/**
 * Provider for the Auth module.
 */
class AuthServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Auth';

    protected string $nameLower = 'auth';

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->scoped(HmacService::class);
        $this->app->scoped(ClientNonceService::class);
        $this->app->scoped(ServerTokenService::class);
        $this->app->scoped(UserAuthenticationService::class);
        $this->app->scoped(NonceSessionService::class);
        $this->app->scoped(SocialiteService::class);
    }

    /**
     * Boot the service provider.
     */
    public function boot(): void
    {
        parent::boot();

        // Register the Auth configuration pipe with the tenant system
        if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
            $this->app->booted(function () {
                \Modules\Tenant\Providers\TenantServiceProvider::registerConfigPipe(
                    \Modules\Auth\Pipes\AuthConfigPipe::class
                );
                
                // Also register the config provider for API responses
                \Modules\Tenant\Providers\TenantServiceProvider::registerConfigProvider(
                    AuthTenantConfigProvider::class
                );
            });
        }
    }
}
