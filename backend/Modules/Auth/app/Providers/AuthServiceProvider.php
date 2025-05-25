<?php

namespace Modules\Auth\Providers;

use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\HmacService;
use Modules\Auth\Services\NonceSessionService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;
use Modules\Auth\Services\UserAuthenticationService;
use Modules\Tenant\Providers\TenantServiceProvider;
use Modules\Auth\Pipes\AuthConfigPipe;
use Modules\Tenant\Enums\TenantConfigVisibility;

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
        if (class_exists(TenantServiceProvider::class)) {
            $this->app->booted(function (): void {
                TenantServiceProvider::registerConfigPipe(
                    AuthConfigPipe::class,
                );

                // Also register the config provider for API responses
                TenantServiceProvider::registerConfigProvider(
                    AuthTenantConfigProvider::class,
                );

                // Register auth-specific seed config
                $this->registerAuthConfigSeeders();
            });
        }
    }

    /**
     * Register auth-specific configuration seeders.
     */
    private function registerAuthConfigSeeders(): void
    {
        // Add auth config to all tiers
        TenantServiceProvider::registerConfigSeederForAllTiers(
            function (string $tier, array $config) {
                $authConfig = [
                    'session_cookie'      => 'quvel_session',
                    'socialite_providers' => ['google'],
                ];

                // Higher tiers get more providers
                if (in_array($tier, ['premium', 'enterprise'])) {
                    $authConfig['socialite_providers'][] = 'microsoft';
                }

                // Enterprise gets longer sessions
                if ($tier === 'enterprise') {
                    $authConfig['session_lifetime'] = 240; // 4 hours
                }

                // Generate unique session cookie for standard+ tiers
                if (in_array($tier, ['standard', 'premium', 'enterprise']) && isset($config['cache_prefix'])) {
                    $prefix                       = str_replace('_', '', $config['cache_prefix']);
                    $authConfig['session_cookie'] = "{$prefix}session";
                }

                return $authConfig;
            },
            20, // Run early (priority 20)
            function (string $tier, array $visibility) {
                // Set visibility for auth config
                return [
                    'session_cookie'      => TenantConfigVisibility::PROTECTED ,
                    'socialite_providers' => TenantConfigVisibility::PUBLIC ,
                    'session_lifetime'    => TenantConfigVisibility::PROTECTED ,
                ];
            }
        );

        // Add recaptcha config if available
        if (env('RECAPTCHA_GOOGLE_SITE_KEY')) {
            TenantServiceProvider::registerConfigSeederForAllTiers(
                fn () => [
                    'recaptcha_google_site_key' => env('RECAPTCHA_GOOGLE_SITE_KEY'),
                ],
                30,
                fn () => [
                    'recaptcha_google_site_key' => TenantConfigVisibility::PUBLIC ,
                ]
            );
        }

        // Add pusher config if available
        if (env('PUSHER_APP_KEY')) {
            TenantServiceProvider::registerConfigSeederForAllTiers(
                fn () => [
                    'pusher_app_key'     => env('PUSHER_APP_KEY'),
                    'pusher_app_cluster' => env('PUSHER_APP_CLUSTER', 'mt1'),
                ],
                30,
                fn () => [
                    'pusher_app_key'     => TenantConfigVisibility::PUBLIC ,
                    'pusher_app_cluster' => TenantConfigVisibility::PUBLIC ,
                ]
            );
        }
    }
}
