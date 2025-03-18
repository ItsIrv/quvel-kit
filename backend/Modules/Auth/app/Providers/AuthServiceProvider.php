<?php

namespace Modules\Auth\Providers;

use App\Providers\ModuleServiceProvider;
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
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(HmacService::class);
        $this->app->singleton(ClientNonceService::class);
        $this->app->singleton(ServerTokenService::class);
        $this->app->singleton(UserAuthenticationService::class);
        $this->app->singleton(NonceSessionService::class);

        $this->app->scoped(SocialiteService::class);
    }
}
