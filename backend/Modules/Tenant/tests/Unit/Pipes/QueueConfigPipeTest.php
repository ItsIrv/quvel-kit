<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\QueueConfigPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the QueueConfigPipe class.
 */
#[CoversClass(QueueConfigPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class QueueConfigPipeTest extends TestCase
{
    private QueueConfigPipe $pipe;
    private ConfigRepository|MockObject $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(ConfigRepository::class);
        $this->pipe   = new QueueConfigPipe();
    }



    public function testHandleAppliesDefaultQueue(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = ['queue_default' => 'redis'];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['queue.default', null, 'sync'],
                ['queue.connections', null, []],
            ]);

        $this->config->expects($this->once())
            ->method('set')
            ->with('queue.default', 'redis');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresDatabaseQueue(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = [
            'queue_connection'     => 'database',
            'queue_database_table' => 'tenant_jobs',
            'queue_name'           => 'tenant-queue',
            'queue_retry_after'    => 120,
            'queue_failed_table'   => 'tenant_failed_jobs',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['queue.connections.database.table', 'tenant_jobs'],
            ['queue.connections.database.queue', 'tenant-queue'],
            ['queue.connections.database.retry_after', 120],
            ['queue.failed.table', 'tenant_failed_jobs'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresRedisQueue(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = [
            'queue_connection'     => 'redis',
            'queue_name'           => 'tenant-redis-queue',
            'queue_retry_after'    => 180,
            'redis_queue_database' => 5,
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['queue.connections.redis.queue', 'tenant-redis-queue'],
            ['queue.connections.redis.retry_after', 180],
            ['queue.connections.redis.connection', 'queue'],
            ['database.redis.queue.database', 5],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresSqsQueue(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = [
            'queue_connection' => 'sqs',
            'aws_sqs_queue'    => 'https://sqs.us-west-2.amazonaws.com/123456789/tenant-queue',
            'aws_sqs_region'   => 'us-west-2',
            'aws_sqs_key'      => 'AKIAIOSFODNN7EXAMPLE',
            'aws_sqs_secret'   => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['queue.connections.sqs.queue', 'https://sqs.us-west-2.amazonaws.com/123456789/tenant-queue'],
            ['queue.connections.sqs.region', 'us-west-2'],
            ['queue.connections.sqs.key', 'AKIAIOSFODNN7EXAMPLE'],
            ['queue.connections.sqs.secret', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    #[DataProvider('partialConfigProvider')]
    public function testHandleWithPartialConfig(array $tenantConfig, int $expectedSetCalls): void
    {
        $tenant = $this->createMock(Tenant::class);

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        if ($expectedSetCalls > 0) {
            $this->config->expects($this->exactly($expectedSetCalls))
                ->method('set');
        } else {
            $this->config->expects($this->never())->method('set');
        }

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public static function partialConfigProvider(): array
    {
        return [
            'database queue with defaults'  => [
                [
                    'queue_connection'     => 'database',
                    'queue_database_table' => 'jobs',
                ],
                3, // table, queue, retry_after
            ],
            'redis queue with defaults'     => [
                [
                    'queue_connection' => 'redis',
                ],
                2, // queue, retry_after
            ],
            'sqs queue without credentials' => [
                [
                    'queue_connection' => 'sqs',
                    'aws_sqs_queue'    => 'tenant-queue',
                ],
                2, // queue, region
            ],
            'only failed table'             => [
                [
                    'queue_failed_table' => 'custom_failed_jobs',
                ],
                1, // failed table
            ],
            'empty config'                  => [
                [],
                0,
            ],
        ];
    }

    public function testHandlePassesDataToNextPipe(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenantConfig = ['queue_default' => 'sync'];
        $nextCalled   = false;

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $this->config->expects($this->once())
            ->method('set')
            ->with('queue.default', 'sync');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) use (&$nextCalled) {
            $nextCalled = true;
            $this->assertArrayHasKey('tenant', $data);
            $this->assertArrayHasKey('config', $data);
            $this->assertArrayHasKey('tenantConfig', $data);
            return $data;
        });

        $this->assertTrue($nextCalled);
        $this->assertSame($tenant, $result['tenant']);
    }



}
