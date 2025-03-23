<?php

namespace App\Providers;

use App\Services\FrontendService;
use App\Services\User\UserCreateService;
use App\Services\User\UserFindService;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Modules\Tenant\Contexts\TenantContext;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(FrontendService::class, function ($app): FrontendService {
            /** @var TenantContext $tenantContext */
            $tenantContext = $app->make(TenantContext::class);

            return new FrontendService(
                $app->make(Redirector::class),
                $tenantContext->get()->config,
            );
        });

        $this->app->singleton(UserCreateService::class);
        $this->app->singleton(UserFindService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        URL::forceScheme('https');
    }
}
