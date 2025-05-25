<?php

namespace Modules\Core\Providers;

use Modules\Core\Http\Middleware\Lang\SetRequestLocale;
use Modules\Core\Http\Middleware\Trace\SetTraceId;
use Modules\Core\Services\FrontendService;
use Modules\Core\Services\User\UserCreateService;
use Modules\Core\Services\User\UserFindService;
use Modules\Core\Contracts\Security\CaptchaVerifierInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Context;

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
        $this->app->singleton(UserCreateService::class);
        $this->app->singleton(UserFindService::class);
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

        $this->app['request']->server->set('HTTPS', 'on');
        $this->app['router']->pushMiddlewareToGroup('web', SetRequestLocale::class);

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
        if (class_exists(\Modules\Tenant\Providers\TenantServiceProvider::class)) {
            $this->app->booted(function () {
                \Modules\Tenant\Providers\TenantServiceProvider::registerConfigProvider(
                    CoreTenantConfigProvider::class,
                );

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
        // Add core config to all tiers
        \Modules\Tenant\Providers\TenantServiceProvider::registerConfigSeederForAllTiers(
            function (string $tier, array $config) {
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

                // Add internal API URL for premium/enterprise tiers
                if (in_array($tier, ['premium', 'enterprise'])) {
                    if (!isset($config['internal_api_url'])) {
                        // Extract just the domain part for internal API
                        $internalDomain                 = str_replace(['https://', 'http://'], '', $apiUrl);
                        $coreConfig['internal_api_url'] = "http://{$internalDomain}:8000";
                    }
                }

                // Special handling for specific enterprise domains (like the seeder does)
                if ($tier === 'enterprise' && $domain === 'api-lan') {
                    $coreConfig['internal_api_url'] = 'http://api-lan:8000';
                }

                return $coreConfig;
            },
            10, // Run very early (priority 10)
            function (string $tier, array $visibility) {
                // Set visibility for core config
                return [
                    'app_name'          => \Modules\Tenant\Enums\TenantConfigVisibility::PUBLIC ,
                    'app_url'           => \Modules\Tenant\Enums\TenantConfigVisibility::PUBLIC ,
                    'frontend_url'      => \Modules\Tenant\Enums\TenantConfigVisibility::PROTECTED ,
                    'mail_from_name'    => \Modules\Tenant\Enums\TenantConfigVisibility::PRIVATE ,
                    'mail_from_address' => \Modules\Tenant\Enums\TenantConfigVisibility::PRIVATE ,
                    'capacitor_scheme'  => \Modules\Tenant\Enums\TenantConfigVisibility::PROTECTED ,
                    'internal_api_url'  => \Modules\Tenant\Enums\TenantConfigVisibility::PROTECTED ,
                ];
            }
        );
    }
}
