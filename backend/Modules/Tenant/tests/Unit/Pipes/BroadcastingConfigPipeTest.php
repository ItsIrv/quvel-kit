<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\BroadcastingConfigPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the BroadcastingConfigPipe class.
 */
#[CoversClass(BroadcastingConfigPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class BroadcastingConfigPipeTest extends TestCase
{
    private BroadcastingConfigPipe $pipe;
    private ConfigRepository|MockObject $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = $this->createMock(ConfigRepository::class);
        $this->pipe = new BroadcastingConfigPipe();
    }

    public function testResolveReturnsCorrectValuesAndVisibility(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'pusher_app_key' => 'test-key',
            'pusher_app_cluster' => 'us-east-1',
        ];

        $result = $this->pipe->resolve($tenant, $tenantConfig);

        $expectedValues = [
            'pusherAppKey' => 'test-key',
            'pusherAppCluster' => 'us-east-1',
        ];

        $expectedVisibility = [
            'pusherAppKey' => 'public',
            'pusherAppCluster' => 'public',
        ];

        $this->assertEquals($expectedValues, $result['values']);
        $this->assertEquals($expectedVisibility, $result['visibility']);
    }

    public function testResolveWithEmptyConfig(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $result = $this->pipe->resolve($tenant, []);
        $this->assertEquals(['values' => [], 'visibility' => []], $result);
    }

    public function testHandlesReturnsCorrectKeys(): void
    {
        $handles = $this->pipe->handles();
        $this->assertContains('pusher_app_key', $handles);
        $this->assertContains('pusher_app_cluster', $handles);
    }

    public function testPriorityReturnsCorrectValue(): void
    {
        $this->assertEquals(45, $this->pipe->priority());
    }
}
