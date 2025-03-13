<?php

namespace Modules\Tenant\Tests\Unit\Providers;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Mockery;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Http\Middleware\TenantMiddleware;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Providers\EventServiceProvider;
use Modules\Tenant\Providers\RouteServiceProvider;
use Modules\Tenant\Providers\TenantServiceProvider;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\Services\TenantResolverService;
use Modules\Tenant\Services\TenantSessionService;
use Modules\Tenant\ValueObjects\TenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use Tests\TestCase;

#[CoversClass(TenantServiceProvider::class)]
#[Group('tenant-module')]
#[Group('tenant-providers')]
class TenantServiceProviderTest extends TestCase
{
    /**
     * Creates a mock instance of TenantServiceProvider with protected methods allowed.
     */
    private function createMockedProvider(): Mockery\MockInterface|TenantServiceProvider
    {
        // Mock the Application instance
        $mockApp = Mockery::mock(Application::class);
        $mockApp->shouldReceive('runningInConsole')->andReturn(false); // Prevent CLI errors
        $mockApp->shouldReceive('make')->andReturnUsing(fn ($class) => Mockery::mock($class));

        // Create a partial mock of the provider
        $mockProvider = $this->mock(TenantServiceProvider::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Use Reflection to set the protected `app` property
        $reflection = new ReflectionClass($mockProvider);
        $property   = $reflection->getProperty('app');
        $property->setAccessible(true);
        $property->setValue($mockProvider, $mockApp); // Inject the mocked app

        return $mockProvider;
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
        $provider->shouldReceive('bootMiddleware')->once();
        $provider->shouldReceive('loadMigrationsFrom')->once()
            ->with(module_path('Tenant', 'database/migrations'));

        $provider->boot();
    }

    /**
     * Test registering middleware.
     */
    public function testBootMiddleware(): void
    {
        Route::shouldReceive('aliasMiddleware')->once()
            ->with('tenant', TenantMiddleware::class);

        $this->createMockedProvider()->bootMiddleware();
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

    /**
     * Test `bindTenantConfigs` correctly updates config values per request.
     */
    public function testBindTenantConfigs(): void
    {
        // Mock TenantConfig
        $tenantConfig = new TenantConfig(
            apiUrl: 'https://api.example.com',
            appUrl: 'https://example.com',
            appName: 'Example Tenant',
            appEnv: 'production',
            mailFromName: 'Example Support',
            mailFromAddress: 'support@example.com',
        );

        // Mock Tenant
        $mockTenant = Mockery::mock(Tenant::class);
        $mockTenant->shouldReceive('getEffectiveConfig')->once()->andReturn($tenantConfig);

        // Mock TenantContext
        $mockTenantContext = Mockery::mock(TenantContext::class);
        $mockTenantContext->shouldReceive('get')->once()->andReturn($mockTenant);

        // Mock Config Repository
        $mockConfig = Mockery::mock(ConfigRepository::class);
        $mockConfig->shouldReceive('set')->once()->with('app.name', 'Example Tenant');
        $mockConfig->shouldReceive('set')->once()->with('app.env', 'production');
        $mockConfig->shouldReceive('set')->once()->with('app.debug', false);
        $mockConfig->shouldReceive('set')->once()->with('app.url', 'https://api.example.com');
        $mockConfig->shouldReceive('set')->once()->with('vite.api_url', 'https://api.example.com');
        $mockConfig->shouldReceive('set')->once()->with('vite.app_url', 'https://example.com');
        $mockConfig->shouldReceive('set')->once()->with('mail.from.name', 'Example Support');
        $mockConfig->shouldReceive('set')->once()->with('mail.from.address', 'support@example.com');
        $mockConfig->shouldReceive('set')->once()->with('session.domain', '.example.com');
        $mockConfig->shouldReceive('set')->once()->with(
            'services.google.redirect',
            'https://api.example.com/auth/provider/google/callback',
        );

        // Mock Application
        $mockApp = Mockery::mock(Application::class);
        $mockApp->shouldReceive('make')->with(TenantContext::class)->andReturn($mockTenantContext);
        $mockApp->shouldReceive('offsetGet')->with('config')->andReturn($mockConfig);
        $mockApp->shouldReceive('rebinding')->once()->with('request', Mockery::type('callable'))->andReturnUsing(
            function ($key, $callback) use ($mockApp): void {
                $callback($mockApp);
            }
        );

        // Create instance of provider
        $provider = new TenantServiceProvider($mockApp);

        // Use reflection to invoke private `bindTenantConfigs()`
        $reflection = new ReflectionClass($provider);
        $method     = $reflection->getMethod('bindTenantConfigs');
        $method->setAccessible(true);

        // Run the method
        $method->invoke($provider);
    }

    public function testBindTenantConfigsLogsCriticalErrorWhenConfigNotFound(): void
    {
        // Mock Tenant returning null for effective config
        $mockTenant = Mockery::mock(Tenant::class);
        $mockTenant->shouldReceive('getEffectiveConfig')->once()->andReturn(null);

        // Mock TenantContext
        $mockTenantContext = Mockery::mock(TenantContext::class);
        $mockTenantContext->shouldReceive('get')->once()->andReturn($mockTenant);

        // Mock Config Repository
        $mockConfig = Mockery::mock(ConfigRepository::class);
        $mockConfig->shouldNotReceive('set'); // No config should be set

        // Mock Application
        $mockApp = Mockery::mock(Application::class);
        $mockApp->shouldReceive('make')->with(TenantContext::class)->andReturn($mockTenantContext);
        $mockApp->shouldReceive('offsetGet')->with('config')->andReturn($mockConfig);
        $mockApp->shouldReceive('rebinding')->once()->with('request', Mockery::type('callable'))->andReturnUsing(
            function ($key, $callback) use ($mockApp) {
                $callback($mockApp);
            }
        );

        // Mock Log Facade to capture log messages
        \Illuminate\Support\Facades\Log::shouldReceive('critical')->once()->with(
            Mockery::on(function ($message) {
                return str_contains($message, "Tenant Config Could Not Be Applied: Tenant config not found");
            }),
        );

        // Create instance of provider
        $provider = new TenantServiceProvider($mockApp);

        // Use reflection to invoke private `bindTenantConfigs()`
        $reflection = new ReflectionClass($provider);
        $method     = $reflection->getMethod('bindTenantConfigs');
        $method->setAccessible(true);

        // Run the method (expecting log entry, NOT an exception)
        $method->invoke($provider);
    }
}
