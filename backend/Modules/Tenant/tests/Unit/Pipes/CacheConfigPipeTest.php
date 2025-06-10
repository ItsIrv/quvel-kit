<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
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
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class CacheConfigPipeTest extends TestCase
{
    private CacheConfigPipe $pipe;
    private ConfigRepository|MockObject $config;
    private Application|MockObject $app;
    private ?Container $originalContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalContainer = Container::getInstance();

        $this->app = $this->createPartialMock(Application::class, [
            'extend',
            'forgetInstance',
            'bound',
            'environment',
        ]);
        $this->config = $this->createMock(ConfigRepository::class);

        Container::setInstance($this->app);

        $this->app->method('bound')->willReturn(false);
        $this->app->method('environment')->willReturn(false);

        $this->pipe = new CacheConfigPipe();
    }

    public function testHandleSetsCacheConfiguration(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->with('public_id')
            ->willReturn('tenant-123');

        $tenantConfig = [
            'cache_store' => 'redis',
            'cache_prefix' => 'custom_prefix',
        ];

        $this->config->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                $this->assertContains($key, ['cache.default', 'cache.prefix']);
                if ($key === 'cache.default') {
                    $this->assertEquals('redis', $value);
                } elseif ($key === 'cache.prefix') {
                    $this->assertEquals('custom_prefix', $value);
                }
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
        $this->assertSame($this->config, $result['config']);
        $this->assertSame($tenantConfig, $result['tenantConfig']);
    }

    public function testHandleSetsDefaultCachePrefix(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->with('public_id')
            ->willReturn('tenant-123');

        $tenantConfig = ['cache_store' => 'redis'];

        $this->config->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                if ($key === 'cache.prefix') {
                    $this->assertEquals('tenant_tenant-123_', $value);
                }
            });

        $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
    }

    public function testHandleWithOnlyPrefixConfiguration(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->with('public_id')
            ->willReturn('tenant-456');

        $tenantConfig = [
            'cache_prefix' => 'my_custom_prefix',
        ];

        $this->config->expects($this->once())
            ->method('set')
            ->with('cache.prefix', 'my_custom_prefix');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
        $this->assertSame($this->config, $result['config']);
        $this->assertSame($tenantConfig, $result['tenantConfig']);
    }

    public function testHandleWithEmptyConfigSetsDefaultPrefix(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->with('public_id')
            ->willReturn('tenant-789');

        $tenantConfig = [];

        $this->config->expects($this->once())
            ->method('set')
            ->with('cache.prefix', 'tenant_tenant-789_');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testResolveReturnsEmptyArray(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->with('public_id')
            ->willReturn('tenant-123');

        $tenantConfig = [
            'cache_store' => 'redis',
            'cache_prefix' => 'custom_prefix',
        ];

        $result = $this->pipe->resolve($tenant, $tenantConfig);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testHandlesReturnsCorrectKeys(): void
    {
        $handles = $this->pipe->handles();

        $expectedKeys = ['cache_store', 'cache_prefix'];

        $this->assertEquals($expectedKeys, $handles);
    }

    public function testPriorityReturnsCorrectValue(): void
    {
        $priority = $this->pipe->priority();
        $this->assertEquals(85, $priority);
    }

    protected function tearDown(): void
    {
        if ($this->originalContainer) {
            Container::setInstance($this->originalContainer);
        }

        parent::tearDown();
    }
}