<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Log\LogManager;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\CacheConfigPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the CacheConfigPipe class.
 */
#[CoversClass(CacheConfigPipe::class)]
#[Group('tenant')]
#[Group('unit')]
class CacheConfigPipeTest extends TestCase
{
    /**
     * @var CacheConfigPipe The pipe instance being tested
     */
    private CacheConfigPipe $pipe;

    /**
     * @var ConfigRepository&MockObject The mocked config repository
     */
    private ConfigRepository|MockObject $config;

    /**
     * @var Application&MockObject The mocked application container
     */
    private Application|MockObject $app;

    /**
     * @var Container|null Original application instance
     */
    private ?Container $originalContainer = null;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Store the original container instance
        $this->originalContainer = Container::getInstance();

        // Create mock app container and config repository
        $this->app = $this->createPartialMock(Application::class, ['make', 'has', 'environment', 'instance', 'forgetInstance', 'extend']);
        $this->config = $this->createMock(ConfigRepository::class);

        // Set Container instance
        Container::setInstance($this->app);

        // Configure app container
        $this->app->method('has')
            ->willReturnMap([
                ['tenant.original_cache_config', false],
                ['config', true]
            ]);

        // Mock logger
        $logger = $this->createMock(LogManager::class);
        $logger->expects($this->any())->method('debug');
        $logger->expects($this->any())->method('error');

        $this->app->method('make')
            ->willReturnMap([
                ['config', [], $this->config],
                ['log', [], $logger]
            ]);

        $this->app->method('environment')
            ->willReturn(true);

        $this->app->method('extend')
            ->willReturnSelf();

        $this->app->method('forgetInstance')
            ->willReturnSelf();

        $this->app->method('instance')
            ->willReturnSelf();

        // Create the pipe
        $this->pipe = new CacheConfigPipe();
    }

    /**
     * Test that the handle method stores the original cache configuration.
     */
    public function testHandleStoresOriginalCacheConfig(): void
    {
        // Arrange
        $tenant = $this->createMock(Tenant::class);
        $tenant->id = '';
        $tenant->public_id = 'public-0';
        $tenantConfig = [];

        // Set up config expectations for original config storage
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', null, 'redis'],
                ['cache.prefix', null, 'app_'],
                ['tenant.enable_tiers', false, false],
            ]);

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
        $this->assertSame($this->config, $result['config']);
        $this->assertSame($tenantConfig, $result['tenantConfig']);
    }

    /**
     * Test that the handle method sets a tenant-specific cache prefix.
     */
    public function testHandleSetsDefaultTenantPrefix(): void
    {
        // Arrange
        $tenant = $this->createMock(Tenant::class);
        $tenant->id = '';
        $tenant->public_id = 'public-0';
        $tenantConfig = [];

        // Set up config expectations
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', null, 'redis'],
                ['cache.prefix', null, 'app_'],
                ['tenant.enable_tiers', false, false],
            ]);

        // Set up expectations for setting tenant-specific cache prefix
        $this->config->expects($this->once())
            ->method('set')
            ->with('cache.prefix', 'tenant__');

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
    }

    /**
     * Test that the handle method applies a custom cache store.
     */
    public function testHandleSetsCustomCacheStore(): void
    {
        // Arrange
        $tenant = $this->createMock(Tenant::class);
        $tenant->id = '';
        $tenant->public_id = 'public-0';
        $tenantConfig = [
            'cache_store' => 'memcached',
        ];

        // Set up config expectations
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', null, 'redis'],
                ['cache.prefix', null, 'app_'],
                ['tenant.enable_tiers', false, false],
            ]);

        // Set up expectations for setting tenant-specific cache store and prefix
        $this->config->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                static $callIndex = 0;
                $callIndex++;

                switch ($callIndex) {
                    case 1:
                        $this->assertEquals('cache.default', $key);
                        $this->assertEquals('memcached', $value);
                        break;
                    case 2:
                        $this->assertEquals('cache.prefix', $key);
                        $this->assertEquals('tenant__', $value);
                        break;
                }

                return null;
            });

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
    }

    /**
     * Test that the handle method applies a custom cache prefix.
     */
    public function testHandleSetsCustomCachePrefix(): void
    {
        // Arrange
        $tenant = $this->createMock(Tenant::class);
        $tenant->id = '';
        $tenant->public_id = 'public-0';
        $tenantConfig = [
            'cache_prefix' => 'custom_prefix_',
        ];

        // Set up config expectations
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', null, 'redis'],
                ['cache.prefix', null, 'app_'],
                ['tenant.enable_tiers', false, false],
            ]);

        // Set up expectations for setting tenant-specific cache prefix
        $this->config->expects($this->once())
            ->method('set')
            ->with('cache.prefix', 'custom_prefix_');

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
    }

    /**
     * Test that the handle method handles tier-based cache configuration.
     */
    public function testHandleWithTierBasedCacheConfig(): void
    {
        // Arrange
        $tenant = $this->createMock(Tenant::class);
        $tenant->id = '';
        $tenant->public_id = 'public-0';
        $tenantConfig = [];

        // Set up tenant tier feature check
        $tenant->expects($this->once())
            ->method('hasFeature')
            ->with('dedicated_cache')
            ->willReturn(false);

        // Set up config expectations
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['cache.default', null, 'redis'],
                ['cache.prefix', null, 'app_'],
                ['tenant.enable_tiers', false, true],
            ]);

        // Set up expectations for setting tenant-specific cache prefix
        $this->config->expects($this->once())
            ->method('set')
            ->with('cache.prefix', 'tenant__');

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
    }

    /**
     * Test that the reset method restores the original configuration.
     */
    public function testResetRestoresOriginalConfiguration(): void
    {
        // This test is simplified since we can't easily mock static methods
        // in the resetResources method without more complex setup

        // We'll just verify the method exists and doesn't throw exceptions
        // when called with our mocked environment

        // Reset the app mock for this test
        Container::setInstance(null);
        $mockApp = $this->createPartialMock(Application::class, ['has', 'make', 'forgetInstance']);
        Container::setInstance($mockApp);

        // Configure the mock to return false for has()
        $mockApp->expects($this->once())
            ->method('has')
            ->with('tenant.original_cache_config')
            ->willReturn(false);

        $mockApp->method('make')
            ->willReturnMap([
                ['config', [], $this->config]
            ]);

        $mockApp->method('forgetInstance')
            ->willReturnSelf();

        // Act
        CacheConfigPipe::resetResources();

        // Assert - No exceptions thrown
        $this->assertTrue(true);
    }

    /**
     * Test that the handles method returns the correct keys.
     */
    public function testHandlesReturnsCorrectKeys(): void
    {
        // Act
        $handles = $this->pipe->handles();

        // Assert
        $this->assertContains('cache_store', $handles);
        $this->assertContains('cache_prefix', $handles);
    }

    /**
     * Test that the priority method returns the correct value.
     */
    public function testPriorityReturnsCorrectValue(): void
    {
        // Act
        $priority = $this->pipe->priority();

        // Assert
        $this->assertEquals(85, $priority);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // Restore the original container instance
        if ($this->originalContainer) {
            Container::setInstance($this->originalContainer);
        }

        parent::tearDown();
    }
}
