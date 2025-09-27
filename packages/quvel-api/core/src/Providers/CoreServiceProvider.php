<?php

declare(strict_types=1);

namespace Quvel\Core;

use Exception;
use Quvel\Core\Http\Middleware\ConfigGate;
use Quvel\Core\Http\Middleware\LocaleMiddleware;
use Quvel\Core\Http\Middleware\RequireInternalRequest;
use Quvel\Core\Http\Middleware\TraceMiddleware;
use Quvel\Core\Http\Middleware\VerifyCaptcha;
use Quvel\Core\Services\CaptchaService;
use Quvel\Core\Services\InternalRequestValidator;
use Quvel\Core\Services\RedirectService;
use Quvel\Core\Services\GoogleRecaptchaVerifier;
use Quvel\Core\Concerns\Security\CaptchaVerifierInterface;
use Illuminate\Support\ServiceProvider;

/**
 * Core package service provider.
 */
class CoreServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/quvel-core.php',
            'quvel-core'
        );

        $this->app->singleton(CaptchaService::class);
        $this->app->singleton(InternalRequestValidator::class);
        $this->app->singleton(GoogleRecaptchaVerifier::class);
        $this->app->singleton(RedirectService::class, function () {
            $service = new RedirectService();
            $service->setBaseUrl(config('quvel-core.frontend.url', 'http://localhost:3000'));
            $service->setCustomScheme(config('quvel-core.frontend.custom_scheme'));

            return $service;
        });

        $this->app->bind(CaptchaVerifierInterface::class, function () {
            $provider = config('quvel-core.captcha.provider', 'recaptcha_v3');

            return match ($provider) {
                'recaptcha_v3' => app(GoogleRecaptchaVerifier::class),
                default => throw new Exception('Invalid captcha provider'),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/quvel-core.php' => config_path('quvel-core.php'),
            ], 'quvel-core-config');

            $this->publishes([
                __DIR__ . '/../lang' => lang_path('vendor/quvel-core'),
            ], 'quvel-core-lang');
        }

        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'quvel-core');

        $router = $this->app['router'];

        $router->aliasMiddleware('quvel.config-gate', ConfigGate::class);
        $router->aliasMiddleware('quvel.locale', LocaleMiddleware::class);
        $router->aliasMiddleware('quvel.trace', TraceMiddleware::class);

        $router->aliasMiddleware('quvel.captcha', VerifyCaptcha::class);
        $router->aliasMiddleware('quvel.internal-only', RequireInternalRequest::class);
    }
}