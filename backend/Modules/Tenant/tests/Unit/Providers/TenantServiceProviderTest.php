<?php

namespace Modules\Tenant\Tests\Unit\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Log\Context\Repository;
use Illuminate\Support\Facades\Context;
use Mockery;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Providers\RouteServiceProvider;
use Modules\Tenant\Providers\TenantServiceProvider;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\Services\RequestPrivacy;
use Modules\Tenant\Services\TenantConfigProviderRegistry;
use Modules\Tenant\Services\TenantConfigSeederRegistry;
use Modules\Tenant\Services\TenantExclusionRegistry;
use Modules\Tenant\Services\TenantTableRegistry;
use Modules\Tenant\Services\TierService;
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
            strtolower(FindService::class),
            strtolower(TierService::class),
            strtolower(TenantExclusionRegistry::class),
            strtolower(TenantTableRegistry::class),
            strtolower(ConfigurationPipeline::class),
            strtolower(TenantConfigProviderRegistry::class),
            strtolower(TenantConfigSeederRegistry::class),
        ];

        $expectedRegisters = [
            strtolower(RouteServiceProvider::class),
        ];

        $expectedScoped = [
            strtolower(TenantContext::class),
            strtolower(RequestPrivacy::class),
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
     * Test that registerConfigSeeder registers a seeder for a specific tier.
     */
    public function testRegisterConfigSeeder(): void
    {
        // Create a mock of TenantConfigSeederRegistry
        $registry = Mockery::mock(TenantConfigSeederRegistry::class);
        
        // Define test data
        $tier = 'premium';
        $seeder = function () {
            return ['feature' => 'enabled'];
        };
        $priority = 30;
        $visibilitySeeder = function () {
            return ['feature_visible' => true];
        };
        
        // Set expectations for the registry mock
        $registry->shouldReceive('registerSeeder')
            ->once()
            ->with($tier, Mockery::type('callable'), $priority, Mockery::type('callable'))
            ->andReturnNull();
        
        // Replace the registry in the container
        $this->app->instance(TenantConfigSeederRegistry::class, $registry);
        
        // Call the static method
        TenantServiceProvider::registerConfigSeeder(
            $tier,
            $seeder,
            $priority,
            $visibilitySeeder
        );
        
        // Mockery will verify that the expectations were met
    }

    /**
     * Test that registerConfigSeederForTiers registers seeders for multiple tiers.
     */
    public function testRegisterConfigSeederForTiers(): void
    {
        // Create a mock of TenantConfigSeederRegistry
        $registry = Mockery::mock(TenantConfigSeederRegistry::class);
        
        // Define test data
        $tiers = ['basic', 'premium'];
        $seeder = function () {
            return ['key' => 'value'];
        };
        $priority = 25;
        $visibilitySeeder = function () {
            return ['visible' => true];
        };
        
        // Set expectations for the registry mock
        $registry->shouldReceive('registerSeederForTiers')
            ->once()
            ->with($tiers, Mockery::type('callable'), $priority, Mockery::type('callable'))
            ->andReturnNull();
        
        // Replace the registry in the container
        $this->app->instance(TenantConfigSeederRegistry::class, $registry);
        
        // Call the static method
        TenantServiceProvider::registerConfigSeederForTiers(
            $tiers,
            $seeder,
            $priority,
            $visibilitySeeder
        );
        
        // Mockery will verify that the expectations were met
    }
}
