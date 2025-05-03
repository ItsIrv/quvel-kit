<?php

namespace Modules\Core\Tests\Unit\Providers;

use Modules\Core\Providers\ModuleServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use ReflectionException;
use Tests\TestCase;

#[CoversClass(ModuleServiceProvider::class)]
#[Group('core-module')]
#[Group('core-providers')]
class ModuleServiceProviderTest extends TestCase
{
    /**
     * Creates a mock instance of ModuleServiceProvider with protected methods allowed.
     *
     * @throws ReflectionException
     */
    private function createMockedProvider(): Mockery\MockInterface|ModuleServiceProvider
    {
        // Mock the Application instance
        $mockApp = Mockery::mock(Application::class);
        $mockApp->shouldReceive('make')->andReturnUsing(fn ($class) => Mockery::mock($class));

        // Create a partial mock of the provider
        $mockProvider = $this->mock(ModuleServiceProvider::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Inject the mocked app
        $reflection = new ReflectionClass($mockProvider);

        $property = $reflection->getProperty('app');
        $property->setValue($mockProvider, $mockApp);

        $property = $reflection->getProperty('name');
        $property->setValue($mockProvider, 'Tenant');

        $property = $reflection->getProperty('nameLower');
        $property->setValue($mockProvider, 'tenant');

        return $mockProvider;
    }

    /**
     * Test registering translations with and without existing directories.
     *
     * @throws ReflectionException
     */
    #[DataProvider('translationDirectoryProvider')]
    public function testRegisterTranslations(bool $dirExists): void
    {
        $provider = $this->createMockedProvider();
        $langPath = resource_path('lang/modules/tenant');

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
     *
     * @throws ReflectionException
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
     *
     * @throws ReflectionException
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
     * Test that `getPublishableViewPaths()` returns only existing directories.
     *
     * @throws ReflectionException
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
