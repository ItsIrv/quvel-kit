<?php

namespace Modules\Core\Providers;

use Modules\Core\Services\FrontendService;
use Modules\Core\Services\User\UserCreateService;
use Modules\Core\Services\User\UserFindService;
use Modules\Core\Contracts\Security\CaptchaVerifierInterface;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;

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
        $this->app->scoped(FrontendService::class, function ($app): FrontendService {
            return (new FrontendService(
                $app->make(Redirector::class),
                $app->make(ResponseFactory::class),
            ))
                ->setUrl(config('frontend.url'))
                ->setCapacitorScheme(config('frontend.capacitor_scheme'))
                ->setIsCapacitor($app->make(Request::class)->hasHeader('X-Capacitor'));
        });

        // Default Captcha Verifier
        $this->app->scoped(CaptchaVerifierInterface::class, function (): CaptchaVerifierInterface {
            $provider = config('core.recaptcha.provider');

            return app($provider);
        });
    }

    /**
     * Boot any application services.
     */
    public function boot(): void
    {
        parent::boot();

        $this->app['request']->server->set('HTTPS', 'on');
    }
}
