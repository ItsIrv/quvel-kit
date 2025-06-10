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
    }
}
