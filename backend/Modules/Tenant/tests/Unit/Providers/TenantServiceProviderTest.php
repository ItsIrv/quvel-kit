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
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantServiceProvider::class)]
#[Group('tenant-module')]
#[Group('providers')]
class TenantServiceProviderTest extends TestCase
{
    /**
     * Creates a mock instance of TenantServiceProvider with protected methods allowed.
     */
    private function createMockedProvider(): Mockery\MockInterface|TenantServiceProvider
    {
        return $this->mock(TenantServiceProvider::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    /**
     * Test that the service provider registers services correctly.
     */
    public function testRegistersServices(): void
    {
        $app = $this->createMock(
            Application::class,
        );

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
        $provider = $this->createMockedProvider();
        $provider->shouldReceive('registerTranslations')->once();
        $provider->shouldReceive('registerConfig')->once();
        $provider->shouldReceive('registerViews')->once();
        $provider->shouldReceive('registerMiddleware')->once();
        $provider->shouldReceive('loadMigrationsFrom')->once()
            ->with(module_path('Tenant', 'database/migrations'));

        $provider->boot();
    }

    /**
     * Test registering middleware.
     */
    public function testRegisterMiddleware(): void
    {
        Route::shouldReceive('aliasMiddleware')->once()
            ->with('tenant', TenantMiddleware::class);

        $this->createMockedProvider()->registerMiddleware();
    }

    /**
     * Test registering translations with and without existing directories.
     */
    #[DataProvider('translationDirectoryProvider')]
    public function testRegisterTranslations(bool $dirExists): void
    {
        $provider = $this->createMockedProvider();
        $langPath = resource_path("lang/modules/tenant");

        $provider->shouldReceive('isDir')->once()
            ->with($langPath)
            ->andReturn($dirExists);
        $provider->shouldReceive('loadTranslationsFrom')->once();
        $provider->shouldReceive('loadJsonTranslationsFrom')->once();

        $provider->registerTranslations();
    }

    public static function translationDirectoryProvider(): array
    {
        return [
            'Directory exists'         => [true],
            'Directory does not exist' => [false],
        ];
    }

    /**
     * Test registering config files.
     */
    public function testRegisterConfig(): void
    {
        $provider = $this->createMockedProvider();

        $provider->shouldReceive('mergeConfigFrom')->once();
        $provider->shouldReceive('publishes')->once();

        $provider->registerConfig();
    }

    /**
     * Test registering views.
     */
    public function testRegisterViews(): void
    {
        Blade::shouldReceive('componentNamespace')->once()->with(
            Mockery::type('string'),
            Mockery::type('string'),
        );

        $provider = $this->createMockedProvider();

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

        $this->assertEquals(
            [],
            $provider->provides(),
        );
    }

    /**
     * Test that `getPublishableViewPaths()` returns only existing directories.
     */
    public function testGetPublishableViewPaths(): void
    {
        config(['view.paths' => ['/path/to/valid', '/path/to/invalid']]);

        $provider = $this->createMockedProvider();

        $provider->shouldReceive('isDir')->once()
            ->with('/path/to/valid/modules/tenant')
            ->andReturn(true);

        $provider->shouldReceive('isDir')->once()
            ->with('/path/to/invalid/modules/tenant')
            ->andReturn(false);

        $result = $provider->getPublishableViewPaths();

        $this->assertEquals(['/path/to/valid/modules/tenant'], $result);
    }
}
