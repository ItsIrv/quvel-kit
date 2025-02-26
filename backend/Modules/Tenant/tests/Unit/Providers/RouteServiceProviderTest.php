<?php

namespace Modules\Tenant\Tests\Unit\Providers;

use Illuminate\Support\Facades\Route;
use Mockery;
use Modules\Tenant\Providers\RouteServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(RouteServiceProvider::class)]
#[Group('tenant-module')]
#[Group('providers')]
class RouteServiceProviderTest extends TestCase
{
    /**
     * Creates a mocked provider instance with protected methods allowed.
     */
    private function createMockedProvider(): Mockery\MockInterface|RouteServiceProvider
    {
        return $this->mock(RouteServiceProvider::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    /**
     * Creates a mock for `group()` that expects `module_path()`.
     */
    private function createGroupMock(string $expectedPath): Mockery\MockInterface
    {
        $groupMock = new class
        {
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
        $mock = $this->createGroupMock(module_path('Tenant', '/routes/web.php'));

        Route::shouldReceive('middleware')
            ->once()
            ->with('web')
            ->andReturn($mock);

        $provider = $this->createMockedProvider();
        $provider->mapWebRoutes();
    }

    /**
     * Tests mapping API routes.
     */
    public function testMapApiRoutes(): void
    {
        $mock = $this->createGroupMock(module_path('Tenant', '/routes/api.php'));

        Route::shouldReceive('middleware')
            ->once()
            ->with('api')
            ->andReturnSelf();

        Route::shouldReceive('prefix')
            ->once()
            ->with('api')
            ->andReturnSelf();

        Route::shouldReceive('name')
            ->once()
            ->with('api.')
            ->andReturn($mock);

        $provider = $this->createMockedProvider();
        $provider->mapApiRoutes();
    }
}
