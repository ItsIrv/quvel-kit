<?php

namespace Tests\Unit\Providers;

use Illuminate\Support\Facades\Route;
use Mockery;
use App\Providers\ModuleRouteServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(ModuleRouteServiceProvider::class)]
#[Group('app-providers')]
class ModuleRouteServiceProviderTest extends TestCase
{
    /**
     * Creates a mocked provider instance with a module name.
     */
    private function createMockedProvider(string $moduleName): Mockery\MockInterface|ModuleRouteServiceProvider
    {
        return new class ($moduleName) extends ModuleRouteServiceProvider
        {
            public function __construct(private string $moduleName)
            {
                $this->name = $this->moduleName;
            }

            public function boot(): void
            {
                parent::boot();
            }

            public function map(): void
            {
                parent::map();
            }
        };
    }

    /**
     * Tests mapping web routes.
     */
    public function testMapWebRoutes(): void
    {
        $moduleName   = 'Tenant';
        $expectedPath = module_path($moduleName, '/routes/web.php');

        Route::shouldReceive('middleware')
            ->once()
            ->with('web')
            ->andReturnSelf();

        Route::shouldReceive('group')
            ->once()
            ->with($expectedPath)
            ->andReturnNull();

        $provider = $this->createMockedProvider($moduleName);
        $provider->mapWebRoutes();
    }

    /**
     * Tests mapping API routes.
     */
    public function testMapApiRoutes(): void
    {
        $moduleName   = 'Tenant';
        $expectedPath = module_path($moduleName, '/routes/api.php');

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
            ->andReturnSelf();

        Route::shouldReceive('group')
            ->once()
            ->with($expectedPath)
            ->andReturnNull();

        $provider = $this->createMockedProvider($moduleName);
        $provider->mapApiRoutes();
    }

    /**
     * Tests mapping channel routes.
     */
    public function testMapChannelRoutes(): void
    {
        $moduleName   = 'Tenant';
        $expectedPath = module_path($moduleName, '/routes/channels.php');

        // Ensure that the `require` call only happens if the file exists.
        $this->mockFunction('file_exists', fn ($path) => $path === $expectedPath);

        $provider = $this->createMockedProvider($moduleName);

        // Capture the `require` call inside `mapChannelRoutes()`
        ob_start();
        $provider->mapChannelRoutes();
        $output = ob_get_clean();

        $this->assertNotEmpty($output, "mapChannelRoutes() should require the file if it exists.");
    }
}
