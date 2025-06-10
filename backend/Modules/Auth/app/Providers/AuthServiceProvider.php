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
        // Add basic auth config to all templates
        TenantServiceProvider::registerConfigSeederForAllTemplates(
            function (string $template, array $config) {
                $authConfig = [
                    'session_cookie'      => 'quvel_session',
                    'socialite_providers' => ['google'],
                    'oauth_credentials'   => [
                            'google' => [
                                'client_id'     => env('GOOGLE_CLIENT_ID', 'your-google-client-id'),
                                'client_secret' => env('GOOGLE_CLIENT_SECRET', 'your-google-client-secret'),
                            ],
                        ],
                ];

                // Generate unique session cookie for standard/isolated templates
                if (in_array($template, ['standard', 'isolated']) && isset($config['cache_prefix'])) {
                    // Extract just the unique ID part from cache_prefix (e.g., "tenant_68337c1aad007_" -> "68337c1aad007")
                    if (preg_match('/tenant_([a-z0-9]+)_?/i', $config['cache_prefix'], $matches)) {
                        $tenantId = $matches[1];
                        // Create a shorter, cleaner session cookie name
                        $authConfig['session_cookie'] = "quvel_{$tenantId}";
                    } else {
                        // Fallback to a simple unique session name
                        $authConfig['session_cookie'] = 'quvel_' . substr(md5($config['cache_prefix']), 0, 8);
                    }
                }

                return $authConfig;
            },
            20, // Run early (priority 20)
            function (string $template, array $visibility) {
                // Set visibility for auth config
                return [
                    'session_cookie'      => TenantConfigVisibility::PROTECTED ,
                    'socialite_providers' => TenantConfigVisibility::PUBLIC ,
                    'session_lifetime'    => TenantConfigVisibility::PROTECTED ,
                ];
            }
        );

        // Add enhanced auth config for isolated template (enterprise-like features)
        TenantServiceProvider::registerConfigSeeder(
            'isolated',
            function (string $template, array $config) {
                return [
                    'socialite_providers' => ['google', 'microsoft'],
                    'oauth_credentials'   => [
                        'google' => [
                            'client_id'     => env('GOOGLE_CLIENT_ID', 'your-google-client-id'),
                            'client_secret' => env('GOOGLE_CLIENT_SECRET', 'your-google-client-secret'),
                        ],
                        'microsoft' => [
                            'client_id'     => env('MICROSOFT_CLIENT_ID', 'your-microsoft-client-id'),
                            'client_secret' => env('MICROSOFT_CLIENT_SECRET', 'your-microsoft-client-secret'),
                        ],
                    ],
                    'session_lifetime' => 240, // 4 hours for isolated tenants
                ];
            },
            21, // Run after base auth config
            function (string $template, array $visibility) {
                return [
                    'oauth_credentials' => TenantConfigVisibility::PRIVATE,
                ];
            }
        );
    }
}
