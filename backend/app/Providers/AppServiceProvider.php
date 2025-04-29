<?php

namespace App\Providers;

use App\Services\FrontendService;
use App\Services\User\UserCreateService;
use App\Services\User\UserFindService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');
    }
}
