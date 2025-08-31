<?php

namespace Modules\Phone\Providers;

use InvalidArgumentException;
use Modules\Core\Providers\ModuleServiceProvider;
use Modules\Phone\Contracts\SmsDriverInterface;
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
        $this->app->singleton(SmsDriverInterface::class, function (): object {
            $driver       = config('phone.sms.default', 'log');
            $driverConfig = config("phone.sms.drivers.{$driver}");

            if (!$driverConfig || !isset($driverConfig['class'])) {
                throw new InvalidArgumentException("SMS driver '{$driver}' is not configured");
            }

            return new $driverConfig['class']();
        });

        $this->app->singleton(SmsService::class);
    }
}
