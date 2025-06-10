<?php

namespace Modules\Core\Providers;

use Modules\Core\Http\Middleware\Lang\SetRequestLocale;
use Modules\Core\Http\Middleware\Trace\SetTraceId;
use Modules\Core\Services\FrontendService;
use Modules\Core\Services\Security\RequestPrivacy;
use Modules\Core\Services\User\UserCreateService;
use Modules\Core\Services\User\UserFindService;
use Modules\Core\Contracts\Security\CaptchaVerifierInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Context;
use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\Providers\TenantServiceProvider;

use function app;
use function config;

class CoreServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Core';

    protected string $nameLower = 'core';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->singleton(UserCreateService::class);
        $this->app->singleton(UserFindService::class);

        $this->app->scoped(RequestPrivacy::class);

        $this->app->scoped(
            FrontendService::class,
            fn ($app) => (new FrontendService(
                $app->make(Redirector::class),
                $app->make(ResponseFactory::class),
                $app->make(Request::class),
            ))
                ->setUrl(config('frontend.url'))
                ->setCapacitorScheme(config('frontend.capacitor_scheme')),
        );

        // Default Captcha Verifier
        $this->app->scoped(
            CaptchaVerifierInterface::class,
            fn (): CaptchaVerifierInterface => app(config('core.recaptcha.provider'))
        );
    }

    /**
     * Boot any application services.
     */
    public function boot(): void
    {
        parent::boot();

        $this->app['url']->forceScheme('https');

        $request = $this->app['request'];
        $request->server->set('HTTPS', 'on');

        if ($request->isFromTrustedProxy() && ($prefix = $request->header('X-Forwarded-Prefix'))) {
            $this->app['url']->forceRootUrl(
                $request->getSchemeAndHttpHost() . $prefix
            );
        }

        $this->app['router']->pushMiddlewareToGroup('web', SetRequestLocale::class);
        $this->app['router']->pushMiddlewareToGroup('api', SetRequestLocale::class);

        $this->app['router']->pushMiddlewareToGroup('web', SetTraceId::class);
        $this->app['router']->pushMiddlewareToGroup('api', SetTraceId::class);

        Context::dehydrating(function (Repository $context): void {
            $context->addHidden('locale', config('app.locale'));
        });

        Context::hydrated(function (Repository $context): void {
            if ($context->hasHidden('locale')) {
                config(['app.locale' => $context->getHidden('locale')]);
            }
        });

        // Register Core tenant config provider if Tenant module exists
        if (class_exists(TenantServiceProvider::class)) {
            $this->app->booted(function () {

                // Register core-specific seed config
                $this->registerCoreConfigSeeders();
            });
        }
    }

    /**
     * Register core-specific configuration seeders.
     */
    private function registerCoreConfigSeeders(): void
    {
        // Add core config to all templates
        TenantServiceProvider::registerConfigSeederForAllTemplates(
            function (string $template, array $config): array {
                // Extract domain info from existing config
                $domain      = $config['domain'] ?? 'example.com';
                $apiUrl      = "https://$domain";
                $frontendUrl = 'https://' . str_replace('api.', '', $domain);

                // Core configuration
                $coreConfig = [
                    'app_name'     => $config['_seed_app_name'] ?? $config['app_name'] ?? 'QuVel',
                    'app_url'      => $apiUrl,
                    'frontend_url' => $frontendUrl,
                ];

                // Add mail configuration using seed parameters
                $coreConfig['mail_from_name'] = $config['_seed_mail_from_name']
                    ?? $config['mail_from_name']
                    ?? $coreConfig['app_name'] . ' Support';

                $coreConfig['mail_from_address'] = $config['_seed_mail_from_address']
                    ?? $config['mail_from_address']
                    ?? 'support@' . str_replace(['https://', 'http://', 'api.'], '', $domain);

                // Add capacitor scheme if provided
                if (isset($config['_seed_capacitor_scheme'])) {
                    $coreConfig['capacitor_scheme'] = $config['_seed_capacitor_scheme'];
                }

                // Add internal API URL for isolated template
                if ($template === 'isolated') {
                    if (!isset($config['internal_api_url'])) {
                        // Extract just the domain part for internal API
                        $internalDomain                 = str_replace(['https://', 'http://'], '', $apiUrl);
                        $coreConfig['internal_api_url'] = "http://{$internalDomain}:8000";
                    }
                }

                // Special handling for specific isolated domains (like the seeder does)
                if ($template === 'isolated' && $domain === 'api-lan') {
                    $coreConfig['internal_api_url'] = 'http://api-lan:8000';
                }

                return $coreConfig;
            },
            10, // Run very early (priority 10)
            fn (string $template, array $visibility): array => [
                'app_name'          => TenantConfigVisibility::PUBLIC ,
                'app_url'           => TenantConfigVisibility::PUBLIC ,
                'frontend_url'      => TenantConfigVisibility::PROTECTED ,
                'mail_from_name'    => TenantConfigVisibility::PRIVATE ,
                'mail_from_address' => TenantConfigVisibility::PRIVATE ,
                'capacitor_scheme'  => TenantConfigVisibility::PROTECTED ,
                'internal_api_url'  => TenantConfigVisibility::PROTECTED ,
            ]
        );

        // Add reCAPTCHA config for tenants
        // Each tenant should have their own keys for proper isolation
        TenantServiceProvider::registerConfigSeederForAllTemplates(
            function (string $template, array $config): array {
                $recaptchaConfig = [];

                // Use seed parameters or environment variables
                if (isset($config['_seed_recaptcha_site_key'])) {
                    $recaptchaConfig['recaptcha_site_key']   = $config['_seed_recaptcha_site_key'];
                    $recaptchaConfig['recaptcha_secret_key'] = $config['_seed_recaptcha_secret_key'] ?? '';
                } elseif (env('RECAPTCHA_GOOGLE_SITE_KEY')) {
                    // Fallback to env for development
                    $recaptchaConfig['recaptcha_site_key']   = env('RECAPTCHA_GOOGLE_SITE_KEY');
                    $recaptchaConfig['recaptcha_secret_key'] = env('RECAPTCHA_GOOGLE_SECRET', '');
                }

                return $recaptchaConfig;
            },
            15, // After core config
            fn (string $template, array $visibility): array => [
                'recaptcha_site_key'   => TenantConfigVisibility::PUBLIC ,
                'recaptcha_secret_key' => TenantConfigVisibility::PRIVATE ,
            ]
        );

        // Add Pusher config for tenants
        TenantServiceProvider::registerConfigSeederForAllTemplates(
            function (string $template, array $config): array {
                $pusherConfig = [];

                // Use seed parameters or environment variables
                if (isset($config['_seed_pusher_app_key'])) {
                    $pusherConfig['pusher_app_key']     = $config['_seed_pusher_app_key'];
                    $pusherConfig['pusher_app_secret']  = $config['_seed_pusher_app_secret'] ?? '';
                    $pusherConfig['pusher_app_id']      = $config['_seed_pusher_app_id'] ?? '';
                    $pusherConfig['pusher_app_cluster'] = $config['_seed_pusher_app_cluster'] ?? 'mt1';
                } elseif (env('PUSHER_APP_KEY')) {
                    // Fallback to env for development
                    $pusherConfig['pusher_app_key']     = env('PUSHER_APP_KEY');
                    $pusherConfig['pusher_app_secret']  = env('PUSHER_APP_SECRET', '');
                    $pusherConfig['pusher_app_id']      = env('PUSHER_APP_ID', '');
                    $pusherConfig['pusher_app_cluster'] = env('PUSHER_APP_CLUSTER', 'mt1');
                }

                return $pusherConfig;
            },
            15, // After core config
            fn (string $template, array $visibility): array => [
                'pusher_app_key'     => TenantConfigVisibility::PUBLIC ,
                'pusher_app_secret'  => TenantConfigVisibility::PRIVATE ,
                'pusher_app_id'      => TenantConfigVisibility::PRIVATE ,
                'pusher_app_cluster' => TenantConfigVisibility::PUBLIC ,
            ]
        );
    }
}
