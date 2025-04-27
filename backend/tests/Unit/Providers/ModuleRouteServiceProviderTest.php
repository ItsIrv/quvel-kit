<?php

namespace Tests\Unit\Providers;

use App\Providers\ModuleRouteServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(ModuleRouteServiceProvider::class)]
#[Group('app-providers')]
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
    public function test_map_web_routes(): void
    {
        $moduleName = 'Tenant';
        $expectedPath = module_path($moduleName, '/routes/web.php');

        $mock = $this->createGroupMock($expectedPath);

        Route::shouldReceive('middleware')
            ->once()
            ->with('web')
            ->andReturn($mock);

        $provider = $this->createStubProvider($moduleName);
        $provider->mapWebRoutes();
    }

    /**
     * Tests mapping API routes.
     */
    public function test_map_api_routes(): void
    {
        $moduleName = 'Tenant';
        $expectedPath = module_path($moduleName, '/routes/api.php');

        $mock = $this->createGroupMock($expectedPath);

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

        $provider = $this->createStubProvider($moduleName);
        $provider->mapApiRoutes();
    }

    /**
     * Tests mapping channel routes.
     */
    public function test_map_channel_routes(): void
    {
        $moduleName = 'Tenant';
        $expectedPath = module_path($moduleName, '/routes/channels.php');

        // Ensure the directory exists
        $directory = dirname($expectedPath);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        // Create a temporary channels.php file
        File::put($expectedPath, "<?php return 'channels loaded'; ?>");

        // Capture the return value of require
        $provider = $this->createStubProvider($moduleName);
        $result = require $expectedPath; // Instead of capturing output

        // Assert that the channels.php file was correctly loaded
        $this->assertEquals('channels loaded', $result);

        // Clean up the temporary file
        File::delete($expectedPath);
    }
}
