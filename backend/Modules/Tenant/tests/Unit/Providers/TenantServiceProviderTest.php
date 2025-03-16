<?php

namespace Modules\Tenant\Tests\Unit\Providers;

use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
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
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use Tests\TestCase;

#[CoversClass(TenantServiceProvider::class)]
#[Group('tenant-module')]
#[Group('tenant-providers')]
class TenantServiceProviderTest extends TestCase
{
    /**
     * Test that the service provider registers services correctly.
     */
    public function test_registers_services(): void
    {
        $app = $this->createMock(
            Application::class,
        );

        $singletons = [];
        $registers = [];

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
    /**
     * Test booting the service provider.
     */
    public function test_boot(): void
    {
        // Use an assertion flag
        $bootMiddlewareCalled = false;

        // Extend the real provider and override `bootMiddleware`
        $provider = new class($this->app) extends TenantServiceProvider
        {
            public bool $bootMiddlewareCalled = false;

            public function bootMiddleware(): void
            {
                $this->bootMiddlewareCalled = true;
            }
        };

        // Call `boot()`
        $provider->boot();

        // Assert that bootMiddleware was actually called
        $this->assertTrue($provider->bootMiddlewareCalled, 'bootMiddleware() was not called.');
    }

    /**
     * Test registering middleware.
     */
    public function test_boot_middleware(): void
    {
        // Mock the Route facade properly
        Route::shouldReceive('aliasMiddleware')
            ->once()
            ->with('tenant', TenantMiddleware::class);

        // Create the provider instance
        $provider = new TenantServiceProvider($this->app);

        // Call bootMiddleware()
        $provider->bootMiddleware();
    }

    /**
     * Test `bindTenantConfigs` correctly updates config values per request.
     *
     * @throws \ReflectionException
     */
    public function test_bind_tenant_configs(): void
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
        $method = $reflection->getMethod('bindTenantConfigs');

        // Run the method
        $method->invoke($provider);
    }

    /**
     * @throws \ReflectionException
     */
    public function test_bind_tenant_configs_logs_critical_error_when_config_not_found(): void
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
                return str_contains($message, 'Tenant Config Could Not Be Applied: Tenant config not found');
            }),
        );

        // Create instance of provider
        $provider = new TenantServiceProvider($mockApp);

        // Use reflection to invoke private `bindTenantConfigs()`
        $reflection = new ReflectionClass($provider);
        $method = $reflection->getMethod('bindTenantConfigs');
        // Run the method (expecting log entry, NOT an exception)
        $method->invoke($provider);
    }
}
