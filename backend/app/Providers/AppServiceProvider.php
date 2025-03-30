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
use Modules\Tenant\Contexts\TenantContext;

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
            /** @var TenantContext $tenantContext */
            $tenantContext = $app->make(TenantContext::class);

            return new FrontendService(
                $tenantContext->get()->config,
                $app->make(Redirector::class),
                $app->make(Request::class),
                $app->make(ResponseFactory::class),
            );
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
