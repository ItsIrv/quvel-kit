<?php

namespace App\Providers;

use App\Services\FrontendService;
use Illuminate\Support\ServiceProvider;
use URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            FrontendService::class,
            function (): FrontendService {
                return new FrontendService(
                    config('quvel.frontend_url'),
                );
            }
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');
    }
}
