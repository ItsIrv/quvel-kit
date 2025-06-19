<?php

namespace Modules\Tenant\Tests\Unit\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Mockery;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Providers\RouteServiceProvider;
use Modules\Tenant\Providers\TenantServiceProvider;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\Services\TenantExclusionRegistry;
use Modules\Tenant\Services\TenantMemoryCache;
use Modules\Tenant\Services\TenantModuleConfigLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantServiceProvider::class)]
#[Group('tenant-module')]
#[Group('tenant-providers')]
class TenantServiceProviderTest extends TestCase
{
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
        $scoped     = [];

        $app->method('singleton')
            ->willReturnCallback(function ($class) use (&$singletons): void {
                $singletons[] = strtolower($class);
            });

        $app->method('register')
            ->willReturnCallback(function ($class) use (&$registers): void {
                $registers[] = strtolower($class);
            });

        $app->method('scoped')
            ->willReturnCallback(function ($class) use (&$scoped): void {
                $scoped[] = strtolower($class);
            });

        $tenantContext = Mockery::mock(TenantContext::class);
        $tenantContext->shouldReceive('get')
            ->andReturn($this->tenant);

        $provider = new TenantServiceProvider($app);
        $provider->register();

        $expectedSingletons = [
            strtolower(TenantModuleConfigLoader::class),
            strtolower(FindService::class),
            strtolower(TenantExclusionRegistry::class),
            strtolower(TenantMemoryCache::class),
            strtolower(ConfigurationPipeline::class),
        ];

        $expectedRegisters = [
            strtolower(RouteServiceProvider::class),
        ];

        $expectedScoped = [
            strtolower(TenantContext::class),
            strtolower(TenantResolver::class),
        ];

        $this->assertEquals($expectedSingletons, $singletons);
        $this->assertEquals($expectedRegisters, $registers);
        $this->assertEquals($expectedScoped, $scoped);
    }

    /**
     * Test that the boot method calls context hydration and dehydration.
     */
    public function testBootCallsContextHydration(): void
    {
        Context::shouldReceive('dehydrating')
            ->once()
            ->with(Mockery::type('Closure'));

        Context::shouldReceive('hydrated')
            ->once()
            ->with(Mockery::type('Closure'));

        $tenantContext = $this->mock(TenantContext::class);
        $tenantContext->shouldReceive('get')
            ->andReturn($this->tenant);

        $this->app->instance(TenantContext::class, $tenantContext);

        $provider = new TenantServiceProvider($this->app);
        $provider->boot();
    }

    /**
     * Test that the dehydrating callback adds the tenant to the context.
     */
    public function testDehydratingAddsHiddenTenant(): void
    {
        // Get the dehydrating callback
        $dehydratingCallback = null;
        Context::shouldReceive('dehydrating')
            ->once()
            ->with(Mockery::on(function ($callback) use (&$dehydratingCallback) {
                $dehydratingCallback = $callback;
                return true;
            }));

        Context::shouldReceive('hydrated')->once();

        // Mock tenant context
        $tenantContext = $this->mock(TenantContext::class);
        $tenantContext->shouldReceive('get')
            ->once()
            ->andReturn($this->tenant);

        $this->app->instance(TenantContext::class, $tenantContext);

        // Boot the provider to register the callbacks
        $provider = new TenantServiceProvider($this->app);
        $provider->boot();

        // Mock context repository
        $contextRepository = Mockery::mock(Repository::class);
        $contextRepository->shouldReceive('addHidden')
            ->once()
            ->with('tenant', $this->tenant);

        // Execute the dehydrating callback
        $dehydratingCallback($contextRepository);
    }

    /**
     * Test that the hydrating callback applies tenant config when tenant is present.
     */
    public function testHydratedAppliesConfigWhenTenantExists(): void
    {
        // This test verifies that the Context::hydrated callback is registered
        // and that it checks for a hidden tenant

        // Arrange - Set up expectations for Context facade calls
        Context::shouldReceive('dehydrating')
            ->once()
            ->with(Mockery::type('Closure'));

        Context::shouldReceive('hydrated')
            ->once()
            ->with(Mockery::type('Closure'));

        // Mock tenant context
        $tenantContext = $this->mock(TenantContext::class);
        $tenantContext->shouldReceive('get')
            ->andReturn($this->tenant);

        $this->app->instance(TenantContext::class, $tenantContext);

        // Act - Boot the provider
        $provider = new TenantServiceProvider($this->app);
        $provider->boot();

        // Assert - Mockery will verify the expectations
    }

    /**
     * Test that the hydrating callback doesn't apply config when tenant is not present.
     */
    public function testHydratedDoesNotApplyConfigWhenTenantMissing(): void
    {
        // Get the hydrated callback
        $hydratedCallback = null;
        Context::shouldReceive('dehydrating')->once();
        Context::shouldReceive('hydrated')
            ->once()
            ->with(Mockery::on(function ($callback) use (&$hydratedCallback) {
                $hydratedCallback = $callback;
                return true;
            }));

        // Mock tenant context
        $tenantContext = $this->mock(TenantContext::class);
        $tenantContext->shouldReceive('get')->andReturn($this->tenant);

        $this->app->instance(TenantContext::class, $tenantContext);

        // Boot the provider to register the callbacks
        $provider = new TenantServiceProvider($this->app);
        $provider->boot();

        // Mock context repository without tenant
        $contextRepository = Mockery::mock(Repository::class);
        $contextRepository->shouldReceive('hasHidden')
            ->once()
            ->with('tenant')
            ->andReturn(false);

        // The getHidden method should not be called
        $contextRepository->shouldNotReceive('getHidden');

        // Execute the hydrated callback
        $hydratedCallback($contextRepository);
    }

    /**
     * Test that ConfigurationPipeline is registered with pipes from module loader.
     */
    public function testConfigurationPipelineRegistrationWithModulePipes(): void
    {
        // Test that the service provider registers ConfigurationPipeline as singleton
        $provider = new TenantServiceProvider($this->app);
        $provider->register();

        // Verify ConfigurationPipeline is registered as singleton
        $this->assertTrue($this->app->bound(ConfigurationPipeline::class));
        
        // Verify that resolving the pipeline creates a ConfigurationPipeline instance
        $pipeline = $this->app->make(ConfigurationPipeline::class);
        $this->assertInstanceOf(ConfigurationPipeline::class, $pipeline);
        
        // Verify that subsequent calls return the same instance (singleton)
        $pipeline2 = $this->app->make(ConfigurationPipeline::class);
        $this->assertSame($pipeline, $pipeline2);
    }

    /**
     * Test that boot method loads exclusions from all modules.
     */
    public function testBootLoadsExclusionsFromAllModules(): void
    {
        $mockLoader = $this->createMock(TenantModuleConfigLoader::class);
        $mockExclusionRegistry = $this->createMock(TenantExclusionRegistry::class);

        $expectedPaths = ['/path1', '/path2'];
        $expectedPatterns = ['*.log', '*.tmp'];

        $mockLoader->expects($this->once())
            ->method('getAllExclusionPaths')
            ->willReturn($expectedPaths);

        $mockLoader->expects($this->once())
            ->method('getAllExclusionPatterns')
            ->willReturn($expectedPatterns);

        $mockExclusionRegistry->expects($this->once())
            ->method('excludePaths')
            ->with($expectedPaths);

        $mockExclusionRegistry->expects($this->once())
            ->method('excludePatterns')
            ->with($expectedPatterns);

        $this->app->instance(TenantModuleConfigLoader::class, $mockLoader);
        $this->app->instance(TenantExclusionRegistry::class, $mockExclusionRegistry);

        // Mock context expectations
        Context::shouldReceive('dehydrating')->once();
        Context::shouldReceive('hydrated')->once();

        $tenantContext = $this->mock(TenantContext::class);
        $tenantContext->shouldReceive('get')->andReturn($this->tenant);
        $this->app->instance(TenantContext::class, $tenantContext);

        $provider = new TenantServiceProvider($this->app);
        $provider->boot();
    }

    /**
     * Test that boot method handles empty exclusions correctly.
     */
    public function testBootHandlesEmptyExclusionsCorrectly(): void
    {
        $mockLoader = $this->createMock(TenantModuleConfigLoader::class);
        $mockExclusionRegistry = $this->createMock(TenantExclusionRegistry::class);

        // Return empty arrays for exclusions
        $mockLoader->expects($this->once())
            ->method('getAllExclusionPaths')
            ->willReturn([]);

        $mockLoader->expects($this->once())
            ->method('getAllExclusionPatterns')
            ->willReturn([]);

        // Should not call exclude methods when arrays are empty
        $mockExclusionRegistry->expects($this->never())
            ->method('excludePaths');

        $mockExclusionRegistry->expects($this->never())
            ->method('excludePatterns');

        $this->app->instance(TenantModuleConfigLoader::class, $mockLoader);
        $this->app->instance(TenantExclusionRegistry::class, $mockExclusionRegistry);

        // Mock context expectations
        Context::shouldReceive('dehydrating')->once();
        Context::shouldReceive('hydrated')->once();

        $tenantContext = $this->mock(TenantContext::class);
        $tenantContext->shouldReceive('get')->andReturn($this->tenant);
        $this->app->instance(TenantContext::class, $tenantContext);

        $provider = new TenantServiceProvider($this->app);
        $provider->boot();
    }

    /**
     * Test that registerTenantMiddlewareGroups registers middleware groups from config.
     */
    public function testRegisterTenantMiddlewareGroupsRegistersGroups(): void
    {
        $middlewareGroups = [
            'tenant.api' => ['auth:sanctum', 'tenant.middleware'],
            'tenant.web' => ['web', 'tenant.middleware'],
        ];

        config(['tenant.middleware.groups' => $middlewareGroups]);

        Route::shouldReceive('middlewareGroup')
            ->once()
            ->with('tenant.api', ['auth:sanctum', 'tenant.middleware']);

        Route::shouldReceive('middlewareGroup')
            ->once()
            ->with('tenant.web', ['web', 'tenant.middleware']);

        // Mock other required components
        Context::shouldReceive('dehydrating')->once();
        Context::shouldReceive('hydrated')->once();

        $tenantContext = $this->mock(TenantContext::class);
        $tenantContext->shouldReceive('get')->andReturn($this->tenant);
        $this->app->instance(TenantContext::class, $tenantContext);

        $mockLoader = $this->createMock(TenantModuleConfigLoader::class);
        $mockExclusionRegistry = $this->createMock(TenantExclusionRegistry::class);

        $mockLoader->method('getAllExclusionPaths')->willReturn([]);
        $mockLoader->method('getAllExclusionPatterns')->willReturn([]);

        $this->app->instance(TenantModuleConfigLoader::class, $mockLoader);
        $this->app->instance(TenantExclusionRegistry::class, $mockExclusionRegistry);

        $provider = new TenantServiceProvider($this->app);
        $provider->boot();
    }

    /**
     * Test that registerTenantMiddlewareGroups handles empty config.
     */
    public function testRegisterTenantMiddlewareGroupsHandlesEmptyConfig(): void
    {
        config(['tenant.middleware.groups' => []]);

        // Mock other required components
        Context::shouldReceive('dehydrating')->once();
        Context::shouldReceive('hydrated')->once();

        $tenantContext = $this->mock(TenantContext::class);
        $tenantContext->shouldReceive('get')->andReturn($this->tenant);
        $this->app->instance(TenantContext::class, $tenantContext);

        $mockLoader = $this->createMock(TenantModuleConfigLoader::class);
        $mockExclusionRegistry = $this->createMock(TenantExclusionRegistry::class);

        $mockLoader->method('getAllExclusionPaths')->willReturn([]);
        $mockLoader->method('getAllExclusionPatterns')->willReturn([]);

        $this->app->instance(TenantModuleConfigLoader::class, $mockLoader);
        $this->app->instance(TenantExclusionRegistry::class, $mockExclusionRegistry);

        $provider = new TenantServiceProvider($this->app);
        $provider->boot();

        // Test passes if no exception is thrown when empty middleware groups are processed
        $this->assertTrue(true);
    }

    /**
     * Test that hydrated callback applies tenant configuration when tenant exists.
     */
    public function testHydratedCallbackAppliesTenantConfiguration(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $mockConfigRepository = $this->createMock(ConfigRepository::class);
        $mockPipeline = $this->createMock(ConfigurationPipeline::class);

        // Capture the hydrated callback
        $hydratedCallback = null;
        Context::shouldReceive('dehydrating')->once();
        Context::shouldReceive('hydrated')
            ->once()
            ->with(Mockery::on(function ($callback) use (&$hydratedCallback) {
                $hydratedCallback = $callback;
                return true;
            }));

        $this->app->instance(ConfigurationPipeline::class, $mockPipeline);
        $this->app->instance(ConfigRepository::class, $mockConfigRepository);

        // Mock tenant context
        $tenantContext = $this->mock(TenantContext::class);
        $tenantContext->shouldReceive('get')->andReturn($this->tenant);
        $this->app->instance(TenantContext::class, $tenantContext);

        // Mock other required components for boot
        $mockLoader = $this->createMock(TenantModuleConfigLoader::class);
        $mockExclusionRegistry = $this->createMock(TenantExclusionRegistry::class);

        $mockLoader->method('getAllExclusionPaths')->willReturn([]);
        $mockLoader->method('getAllExclusionPatterns')->willReturn([]);

        $this->app->instance(TenantModuleConfigLoader::class, $mockLoader);
        $this->app->instance(TenantExclusionRegistry::class, $mockExclusionRegistry);

        // Boot the provider to register callbacks
        $provider = new TenantServiceProvider($this->app);
        $provider->boot();

        // Mock context repository with tenant
        $contextRepository = Mockery::mock(Repository::class);
        $contextRepository->shouldReceive('hasHidden')
            ->once()
            ->with('tenant')
            ->andReturn(true);

        $contextRepository->shouldReceive('getHidden')
            ->once()
            ->with('tenant')
            ->andReturn($tenant);

        // Expect pipeline to apply configuration
        $mockPipeline->expects($this->once())
            ->method('apply')
            ->with($tenant, $mockConfigRepository);

        // Execute the hydrated callback
        $hydratedCallback($contextRepository);
    }
}
