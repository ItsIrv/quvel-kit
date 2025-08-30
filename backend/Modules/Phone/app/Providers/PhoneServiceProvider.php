<?php

namespace Modules\Phone\Providers;

use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Phone\Services\OtpCacheService;
use Modules\Phone\Services\PhoneService;

/**
 * Provider for the Phone module.
 */
class PhoneServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Phone';

    protected string $nameLower = 'phone';

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);

        $this->app->singleton(OtpCacheService::class);
        $this->app->singleton(PhoneService::class);
    }
}
