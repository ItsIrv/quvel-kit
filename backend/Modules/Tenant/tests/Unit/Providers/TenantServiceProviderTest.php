<?php

namespace Modules\Tenant\Tests\Unit\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Mockery;
use Modules\Tenant\app\Http\Middleware\TenantMiddleware;
use Modules\Tenant\Providers\EventServiceProvider;
use Modules\Tenant\Providers\RouteServiceProvider;
use Modules\Tenant\Providers\TenantServiceProvider;
use Modules\Tenant\app\Services\TenantFindService;
use Modules\Tenant\app\Services\TenantResolverService;
use Modules\Tenant\app\Services\TenantSessionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantServiceProvider::class)]
#[Group('tenant-module')]
#[Group('providers')]
class TenantServiceProviderTest extends TestCase
{
    /**
     * Test that the service provider registers services correctly.
     */
    public function testRegistersServices(): void
    {
        $app = $this->createMock(Application::class);

        $singletons = [];
        $registers  = [];

        $app->method('singleton')
            ->willReturnCallback(function ($class) use (&$singletons): void {
                $singletons[] = strtolower($class);
            });

        $app->method('register')
            ->willReturnCallback(function ($class) use (&$registers): void {
                $registers[] = strtolower($class);
            });

        $provider = new TenantServiceProvider($app);
        $provider->register();

        $expectedSingletons = [
            strtolower(TenantSessionService::class),
            strtolower(TenantFindService::class),
            strtolower(TenantResolverService::class),
        ];

        $expectedRegisters = [
            strtolower(EventServiceProvider::class),
            strtolower(RouteServiceProvider::class),
        ];

        $this->assertEquals($expectedSingletons, $singletons);
        $this->assertEquals($expectedRegisters, $registers);
    }

    /**
     * Test booting the service provider.
     */
    public function testBoot(): void
    {
        $provider = Mockery::mock(
            TenantServiceProvider::class,
            [$this->app],
        )->makePartial();

        $provider->shouldAllowMockingProtectedMethods();

        // Expect these methods to be called once
        $provider->shouldReceive('registerTranslations')->once();
        $provider->shouldReceive('registerConfig')->once();
        $provider->shouldReceive('registerViews')->once();
        $provider->shouldReceive('registerMiddleware')->once();
        $provider->shouldReceive('loadMigrationsFrom')->once()
            ->with(module_path('Tenant', 'database/migrations'));

        // Execute the boot method
        $provider->boot();
    }

    /**
     * Test registering middleware.
     */
    public function testRegisterMiddleware(): void
    {
        Route::shouldReceive('aliasMiddleware')
            ->once()
            ->with('tenant', TenantMiddleware::class);

        $provider = new TenantServiceProvider($this->app);
        $provider->registerMiddleware();
    }

    /**
     * Test registering translations.
     */
    public function testRegisterTranslations(): void
    {
        $provider = Mockery::mock(TenantServiceProvider::class, [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $provider->shouldReceive('loadTranslationsFrom')->once();
        $provider->shouldReceive('loadJsonTranslationsFrom')->once();

        $provider->registerTranslations();
    }

    /**
     * Test registering config files.
     */
    public function testRegisterConfig(): void
    {
        $provider = Mockery::mock(TenantServiceProvider::class, [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $provider->shouldReceive('mergeConfigFrom')->once();
        $provider->shouldReceive('publishes')->once();

        $provider->registerConfig();
    }

    /**
     * Test registering views.
     */
    public function testRegisterViews(): void
    {
        Blade::shouldReceive('componentNamespace')
            ->once()
            ->with(
                Mockery::type('string'),
                Mockery::type('string'),
            );

        $provider = Mockery::mock(TenantServiceProvider::class, [$this->app])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $provider->shouldReceive('loadViewsFrom')->once();
        $provider->shouldReceive('publishes')->once();

        $provider->registerViews();
    }

    /**
     * Test that the `provides()` method returns the expected services.
     */
    public function testProvides(): void
    {
        $provider = new TenantServiceProvider($this->app);
        $this->assertEquals([], $provider->provides());
    }
}
