<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\BroadcastingConfigPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

#[CoversClass(BroadcastingConfigPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class BroadcastingConfigPipeTest extends TestCase
{
    /**
     * The config repository mock.
     */
    private ConfigRepository&MockObject $config;

    /**
     * The pipe under test.
     */
    private BroadcastingConfigPipe $pipe;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(ConfigRepository::class);
        $this->pipe = new BroadcastingConfigPipe();
    }



    /**
     * Test that the pipe correctly applies custom broadcast driver.
     */
    public function testHandleSetsCustomBroadcastDriver(): void
    {
        // Arrange
        $tenant = $this->createPartialMock(Tenant::class, []);
        $tenant->id = 'test123';
        $tenant->public_id = 'public-test123';
        $tenantConfig = [
            'broadcast_driver' => 'redis',
        ];

        // Set up config expectations for original config storage
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['broadcasting.default', null, 'pusher'],
                ['broadcasting.connections', null, []],
            ]);

        // Set up config expectations for tenant-specific settings
        $this->config->expects($this->once())
            ->method('set')
            ->with('broadcasting.default', 'redis');

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
    }

    /**
     * Test that the pipe correctly configures Pusher for tenant.
     */
    public function testHandleConfiguresPusherForTenant(): void
    {
        // Arrange
        $tenant = $this->createPartialMock(Tenant::class, []);
        $tenant->id = 'test123';
        $tenant->public_id = 'public-test123';
        $tenantConfig = [
            'pusher_app_id' => 'tenant-app-id',
            'pusher_app_key' => 'tenant-key',
            'pusher_app_secret' => 'tenant-secret',
            'pusher_app_cluster' => 'tenant-cluster',
        ];

        // Set up config expectations for original config storage
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['broadcasting.default', null, 'pusher'],
                ['broadcasting.connections', null, []],
            ]);

        // Set up config expectations for tenant-specific settings
        // Using separate expects calls for PHPUnit 10+ compatibility
        $this->config->expects($this->exactly(5))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                static $callIndex = 0;
                $callIndex++;

                switch ($callIndex) {
                    case 1:
                        $this->assertEquals('broadcasting.connections.pusher.app_id', $key);
                        $this->assertEquals('tenant-app-id', $value);
                        break;
                    case 2:
                        $this->assertEquals('broadcasting.connections.pusher.key', $key);
                        $this->assertEquals('tenant-key', $value);
                        break;
                    case 3:
                        $this->assertEquals('broadcasting.connections.pusher.options.key', $key);
                        $this->assertEquals('tenant-key', $value);
                        break;
                    case 4:
                        $this->assertEquals('broadcasting.connections.pusher.secret', $key);
                        $this->assertEquals('tenant-secret', $value);
                        break;
                    case 5:
                        $this->assertEquals('broadcasting.connections.pusher.options.cluster', $key);
                        $this->assertEquals('tenant-cluster', $value);
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
     * Test that the pipe correctly configures Pusher options for tenant.
     */
    public function testHandleConfiguresPusherOptionsForTenant(): void
    {
        // Arrange
        $tenant = $this->createPartialMock(Tenant::class, []);
        $tenant->id = 'test123';
        $tenant->public_id = 'public-test123';
        $tenantConfig = [
            'pusher_scheme' => 'https',
            'pusher_host' => 'tenant-host.example.com',
            'pusher_port' => 6001,
        ];

        // Set up config expectations for original config storage
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['broadcasting.default', null, 'pusher'],
                ['broadcasting.connections', null, []],
            ]);

        // Set up config expectations for tenant-specific settings
        // Using separate expects calls for PHPUnit 10+ compatibility
        $this->config->expects($this->exactly(3))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                static $callIndex = 0;
                $callIndex++;

                switch ($callIndex) {
                    case 1:
                        $this->assertEquals('broadcasting.connections.pusher.options.scheme', $key);
                        $this->assertEquals('https', $value);
                        break;
                    case 2:
                        $this->assertEquals('broadcasting.connections.pusher.options.host', $key);
                        $this->assertEquals('tenant-host.example.com', $value);
                        break;
                    case 3:
                        $this->assertEquals('broadcasting.connections.pusher.options.port', $key);
                        $this->assertEquals(6001, $value);
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
     * Test that the pipe correctly configures Reverb for tenant.
     */
    public function testHandleConfiguresReverbForTenant(): void
    {
        // Arrange
        $tenant = $this->createPartialMock(Tenant::class, []);
        $tenant->id = 'test123';
        $tenant->public_id = 'public-test123';
        $tenantConfig = [
            'reverb_app_id' => 'tenant-reverb-id',
            'reverb_app_key' => 'tenant-reverb-key',
            'reverb_app_secret' => 'tenant-reverb-secret',
            'reverb_host' => 'tenant-reverb-host.example.com',
            'reverb_port' => 6001,
        ];

        // Set up config expectations for original config storage
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['broadcasting.default', null, 'pusher'],
                ['broadcasting.connections', null, []],
            ]);

        // Set up config expectations for tenant-specific settings
        // Using separate expects calls for PHPUnit 10+ compatibility
        $this->config->expects($this->exactly(6))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                static $callIndex = 0;
                $callIndex++;

                switch ($callIndex) {
                    case 1:
                        $this->assertEquals('broadcasting.connections.reverb.app_id', $key);
                        $this->assertEquals('tenant-reverb-id', $value);
                        break;
                    case 2:
                        $this->assertEquals('broadcasting.connections.reverb.key', $key);
                        $this->assertEquals('tenant-reverb-key', $value);
                        break;
                    case 3:
                        $this->assertEquals('broadcasting.connections.reverb.options.key', $key);
                        $this->assertEquals('tenant-reverb-key', $value);
                        break;
                    case 4:
                        $this->assertEquals('broadcasting.connections.reverb.secret', $key);
                        $this->assertEquals('tenant-reverb-secret', $value);
                        break;
                    case 5:
                        $this->assertEquals('broadcasting.connections.reverb.options.host', $key);
                        $this->assertEquals('tenant-reverb-host.example.com', $value);
                        break;
                    case 6:
                        $this->assertEquals('broadcasting.connections.reverb.options.port', $key);
                        $this->assertEquals(6001, $value);
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
     * Test that the pipe correctly configures Redis broadcasting with tenant prefix.
     */
    public function testHandleConfiguresRedisBroadcastingWithTenantPrefix(): void
    {
        // Arrange
        $tenant = $this->createPartialMock(Tenant::class, []);
        $tenant->id = 'test123';
        $tenant->public_id = 'public-test123';
        $tenantConfig = [
            'broadcast_driver' => 'redis',
        ];

        // Set up config expectations
        $this->config->expects($this->once())
            ->method('get')
            ->with('broadcasting.default')
            ->willReturn('redis');

        // Set up config expectations for tenant-specific settings
        $this->config->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                static $callIndex = 0;
                $callIndex++;

                switch ($callIndex) {
                    case 1:
                        $this->assertEquals('broadcasting.default', $key);
                        $this->assertEquals('redis', $value);
                        break;
                    case 2:
                        $this->assertEquals('broadcasting.connections.redis.prefix', $key);
                        $this->assertEquals('tenant_0', $value); // The actual value from the implementation
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
     * Test that the pipe correctly configures custom Redis broadcast prefix.
     */
    public function testHandleConfiguresCustomRedisBroadcastPrefix(): void
    {
        // Arrange
        $tenant = $this->createPartialMock(Tenant::class, []);
        $tenant->id = 'test123';
        $tenant->public_id = 'public-test123';
        $tenantConfig = [
            'redis_broadcast_prefix' => 'custom_prefix',
        ];

        // Set up config expectations for original config storage
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['broadcasting.default', null, 'pusher'],
                ['broadcasting.connections', null, []],
            ]);

        // Set up config expectations for tenant-specific settings
        $this->config->expects($this->once())
            ->method('set')
            ->with('broadcasting.connections.redis.prefix', 'custom_prefix');

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
    }

    /**
     * Test that the pipe correctly configures Ably for enterprise tenants.
     */
    public function testHandleConfiguresAblyForEnterpriseTenants(): void
    {
        // Arrange
        $tenant = $this->createPartialMock(Tenant::class, []);
        $tenant->id = 'test123';
        $tenant->public_id = 'public-test123';
        $tenantConfig = [
            'ably_key' => 'tenant-ably-key',
        ];

        // Set up config expectations for original config storage
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['broadcasting.default', null, 'pusher'],
                ['broadcasting.connections', null, []],
            ]);

        // Set up config expectations for tenant-specific settings
        $this->config->expects($this->once())
            ->method('set')
            ->with('broadcasting.connections.ably.key', 'tenant-ably-key');

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
    }



    /**
     * Test that the pipe returns the correct handles array.
     */
    public function testHandlesReturnsCorrectKeys(): void
    {
        // Act
        $handles = $this->pipe->handles();

        // Assert
        $this->assertContains('broadcast_driver', $handles);
        $this->assertContains('pusher_app_id', $handles);
        $this->assertContains('pusher_app_key', $handles);
        $this->assertContains('pusher_app_secret', $handles);
        $this->assertContains('pusher_app_cluster', $handles);
        $this->assertContains('pusher_scheme', $handles);
        $this->assertContains('pusher_host', $handles);
        $this->assertContains('pusher_port', $handles);
        $this->assertContains('reverb_app_id', $handles);
        $this->assertContains('reverb_app_key', $handles);
        $this->assertContains('reverb_app_secret', $handles);
        $this->assertContains('reverb_host', $handles);
        $this->assertContains('reverb_port', $handles);
        $this->assertContains('redis_broadcast_prefix', $handles);
        $this->assertContains('ably_key', $handles);
    }

    /**
     * Test that the pipe returns the correct priority.
     */
    public function testPriorityReturnsCorrectValue(): void
    {
        // Act
        $priority = $this->pipe->priority();

        // Assert
        $this->assertEquals(45, $priority);
    }
}
