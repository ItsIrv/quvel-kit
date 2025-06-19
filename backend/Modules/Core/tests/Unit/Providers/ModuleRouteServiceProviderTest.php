<?php

namespace Modules\Core\Tests\Unit\Providers;

use Modules\Core\Providers\ModuleRouteServiceProvider;
use Illuminate\Support\Facades\Route;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(ModuleRouteServiceProvider::class)]
#[Group('core-module')]
#[Group('core-providers')]
class ModuleRouteServiceProviderTest extends TestCase
{
    /**
     * Creates a stub provider instance with the module name set.
     */
    private function createStubProvider(string $moduleName): ModuleRouteServiceProvider
    {
        return new class ($moduleName) extends ModuleRouteServiceProvider {
            public function __construct(protected string $name)
            {
                parent::__construct($this->name);
            }

            public function mapWebRoutes(): void
            {
                parent::mapWebRoutes();
            }

            public function mapApiRoutes(): void
            {
                parent::mapApiRoutes();
            }

            public function mapChannelRoutes(): void
            {
                parent::mapChannelRoutes();
            }
        };
    }

    /**
     * Creates a mock for `group()` that expects `module_path()`.
     */
    private function createGroupMock(string $expectedPath): Mockery\MockInterface
    {
        $groupMock = new class () {
            public function group(string $path): void
            {
                // Placeholder for group method
            }
        };

        return Mockery::mock($groupMock)
            ->shouldReceive('group')
            ->once()
            ->with($expectedPath)
            ->andReturnNull()
            ->getMock();
    }

    /**
     * Tests mapping web routes.
     */
    public function testMapWebRoutes(): void
    {
        $moduleName = 'Tenant';
        
        // Since Tenant module doesn't have web.php, middleware should not be called
        Route::shouldReceive('middleware')
            ->never();

        $provider = $this->createStubProvider($moduleName);
        $provider->mapWebRoutes();
    }

    /**
     * Tests mapping API routes.
     */
    public function testMapApiRoutes(): void
    {
        $moduleName = 'Tenant';
        
        // Since Tenant module doesn't have api.php, middleware should not be called
        Route::shouldReceive('middleware')
            ->never();

        Route::shouldReceive('prefix')
            ->never();

        Route::shouldReceive('name')
            ->never();

        $provider = $this->createStubProvider($moduleName);
        $provider->mapApiRoutes();
    }
}
