<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Foundation\Application;
use Modules\Tenant\Logs\Pipes\RedisConfigPipeLogs;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\RedisConfigPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the RedisConfigPipe class.
 */
#[CoversClass(RedisConfigPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class RedisConfigPipeTest extends TestCase
{
    /**
     * @var RedisConfigPipe The pipe instance being tested
     */
    private RedisConfigPipe $pipe;

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
        $this->app    = $this->createPartialMock(Application::class, [
            'make',
            'bound',
            'environment',
            'instance',
            'forgetInstance',
            'extend',
        ]);
        $this->config = $this->createMock(ConfigRepository::class);

        // Set Container instance
        Container::setInstance($this->app);

        // Configure app container defaults
        $this->app->method('bound')
            ->willReturnMap([
                [RedisFactory::class, true],
                [RedisConfigPipeLogs::class, false],
            ]);

        $this->app->method('environment')
            ->willReturn(false);

        // Create the pipe
        $this->pipe = new RedisConfigPipe();
    }

    /**
     * Test that the handle method sets Redis configuration values.
     */
    public function testHandleSetsRedisConfiguration(): void
    {
        // Arrange
        $tenant     = $this->createMock(Tenant::class);
        $tenant->id = 123;

        $tenantConfig = [
            'redis_client'   => 'predis',
            'redis_host'     => '127.0.0.1',
            'redis_password' => 'secret',
            'redis_port'     => 6380,
        ];

        // Set up config expectations
        $expectedSetCalls = [
            ['database.redis.client', 'predis'],
            ['database.redis.default.host', '127.0.0.1'],
            ['database.redis.default.password', 'secret'],
            ['database.redis.default.port', 6380],
            ['database.redis.default.prefix', 'tenant_123:'],
            ['database.redis.cache.prefix', 'tenant_123:'],
        ];

        $this->config->expects($this->exactly(count($expectedSetCalls)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSetCalls) {
                $expectedCall = array_shift($expectedSetCalls);
                $this->assertEquals($expectedCall[0], $key);
                $this->assertEquals($expectedCall[1], $value);
            });

        // Mock Redis extension check
        $this->mockRedisAvailable();

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
     * Test that the handle method sets custom Redis prefix.
     */
    public function testHandleSetsCustomRedisPrefix(): void
    {
        // Arrange
        $tenant     = $this->createMock(Tenant::class);
        $tenant->id = 123;

        $tenantConfig = [
            'redis_prefix' => 'custom_prefix:',
        ];

        // Set up config expectations
        $expectedSetCalls = [
            ['database.redis.default.prefix', 'custom_prefix:'],
            ['database.redis.cache.prefix', 'custom_prefix:'],
        ];

        $this->config->expects($this->exactly(count($expectedSetCalls)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSetCalls) {
                $expectedCall = array_shift($expectedSetCalls);
                $this->assertEquals($expectedCall[0], $key);
                $this->assertEquals($expectedCall[1], $value);
            });

        // Mock Redis extension check
        $this->mockRedisAvailable();

        // Act
        $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
    }

    /**
     * Test that the handle method sets default tenant prefix when no custom prefix.
     */
    public function testHandleSetsDefaultTenantPrefix(): void
    {
        // Arrange
        $tenant     = $this->createMock(Tenant::class);
        $tenant->id = 456;

        $tenantConfig = []; // No redis_prefix specified

        // Set up config expectations for default tenant prefix
        $expectedSetCalls = [
            ['database.redis.default.prefix', 'tenant_456:'],
            ['database.redis.cache.prefix', 'tenant_456:'],
        ];

        $this->config->expects($this->exactly(count($expectedSetCalls)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSetCalls) {
                $expectedCall = array_shift($expectedSetCalls);
                $this->assertEquals($expectedCall[0], $key);
                $this->assertEquals($expectedCall[1], $value);
            });

        // Mock Redis extension check
        $this->mockRedisAvailable();

        // Act
        $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
    }

    /**
     * Test that the handle method skips configuration when Redis is not available.
     */
    public function testHandleSkipsWhenRedisNotAvailable(): void
    {
        // Arrange
        $tenant     = $this->createMock(Tenant::class);
        $tenant->id = 123;

        $tenantConfig = [
            'redis_host' => '127.0.0.1',
        ];

        // Mock Redis as not available
        $this->app->method('bound')
            ->with(RedisFactory::class)
            ->willReturn(false);

        // Config should not be called to set anything
        $this->config->expects($this->never())
            ->method('set');

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
    }

    /**
     * Test that the handle method refreshes Redis connections when configuration changes.
     */
    public function testHandleRefreshesRedisConnections(): void
    {
        // Arrange
        $tenant     = $this->createMock(Tenant::class);
        $tenant->id = 123;

        $tenantConfig = [
            'redis_host' => '127.0.0.1',
        ];

        // Set up config expectations
        $this->config->expects($this->exactly(2))
            ->method('set');

        // Expect Redis connections to be refreshed
        $this->app->expects($this->once())
            ->method('extend')
            ->with(RedisFactory::class);

        $this->app->expects($this->exactly(2))
            ->method('forgetInstance')
            ->willReturnCallback(function ($abstract) {
                $this->assertContains($abstract, [RedisFactory::class, 'redis']);
            });

        // Mock Redis extension check
        $this->mockRedisAvailable();

        // Act
        $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
    }

    /**
     * Test that the handles method returns the correct keys.
     */
    public function testHandlesReturnsCorrectKeys(): void
    {
        // Act
        $handles = $this->pipe->handles();

        // Assert
        $expectedKeys = [
            'redis_client',
            'redis_host',
            'redis_password',
            'redis_port',
            'redis_prefix',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertContains($key, $handles);
        }
    }

    /**
     * Test that the priority method returns the correct value.
     */
    public function testPriorityReturnsCorrectValue(): void
    {
        // Act
        $priority = $this->pipe->priority();

        // Assert
        $this->assertEquals(84, $priority);
    }

    /**
     * Test that the handle method handles empty tenant config gracefully.
     */
    public function testHandleWithEmptyTenantConfig(): void
    {
        // Arrange
        $tenant       = $this->createMock(Tenant::class);
        $tenant->id   = 123;
        $tenantConfig = [];

        // Should still set default tenant prefix
        $this->config->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                $this->assertContains($key, [
                    'database.redis.default.prefix',
                    'database.redis.cache.prefix',
                ]);
                $this->assertEquals('tenant_123:', $value);
            });

        // Mock Redis extension check
        $this->mockRedisAvailable();

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
    }

    /**
     * Mock Redis as available for testing.
     */
    private function mockRedisAvailable(): void
    {
        // Mock that Redis extension is loaded and classes exist
        if (!extension_loaded('redis')) {
            $this->markTestSkipped('Redis extension not loaded - mocking availability');
        }

        $this->app->method('bound')
            ->willReturnMap([
                [RedisFactory::class, true],
                [RedisConfigPipeLogs::class, false],
            ]);
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
