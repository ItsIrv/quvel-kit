<?php

namespace Modules\Phone\Providers;

use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Phone\Contracts\SmsProviderInterface;
use Modules\Phone\Services\OtpCacheService;
use Modules\Phone\Services\PhoneService;
use Modules\Phone\Services\SmsService;

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

        // Bind SMS provider based on config
        $this->app->singleton(SmsProviderInterface::class, function () {
            $provider       = config('phone.sms.default', 'log');
            $providerConfig = config("phone.sms.providers.{$provider}");

            if (!$providerConfig || !isset($providerConfig['class'])) {
                throw new \InvalidArgumentException("SMS provider '{$provider}' is not configured");
            }

            return new $providerConfig['class']();
        });

        $this->app->singleton(SmsService::class);
    }
}
