<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\FilesystemConfigPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * Unit tests for the FilesystemConfigPipe class.
 */
#[CoversClass(FilesystemConfigPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class FilesystemConfigPipeTest extends TestCase
{
    private FilesystemConfigPipe $pipe;
    private ConfigRepository|MockObject $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(ConfigRepository::class);
        $this->pipe   = new FilesystemConfigPipe();
    }

    public function testHandleAppliesDefaultAndCloudFilesystems(): void
    {
        $tenant       = new Tenant();
        $tenant->id   = '123';
        $tenantConfig = [
            'filesystem_default' => 's3',
            'filesystem_cloud'   => 'gcs',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['filesystems.default', 's3'],
            ['filesystems.cloud', 'gcs'],
        ];

        $foundSets = 0;
        $this->config->expects($this->atLeast(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use ($expectedSets, &$foundSets) {
                foreach ($expectedSets as $expected) {
                    if ($expected[0] === $key && $expected[1] === $value) {
                        $foundSets++;
                        break;
                    }
                }
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleAppliesCustomLocalAndPublicRoots(): void
    {
        $tenant       = new Tenant();
        $tenant->id   = '123';
        $tenantConfig = [
            'filesystem_local_root'  => '/custom/local/path',
            'filesystem_public_root' => '/custom/public/path',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['filesystems.disks.local.root', '/custom/local/path'],
            ['filesystems.disks.public.root', '/custom/public/path'],
        ];

        $callIndex = 0;
        $this->config->expects($this->atLeast(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets, &$callIndex) {
                if ($callIndex < count($expectedSets)) {
                    $expected = $expectedSets[$callIndex];
                    $this->assertEquals($expected[0], $key);
                    $this->assertEquals($expected[1], $value);
                    $callIndex++;
                }
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleAppliesDefaultTenantIsolation(): void
    {
        $tenant            = new Tenant();
        $tenant->id        = '456';
        $tenant->public_id = 'test-public-id';
        $tenantConfig      = [];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['filesystems.default', null, 'local'],
                ['filesystems.cloud', null, 's3'],
                ['filesystems.disks', null, []],
                ['app.url', null, 'https://app.com'],
            ]);

        $expectedSets = [
            ['filesystems.disks.local.root', storage_path('app/tenants/test-public-id')],
            ['filesystems.disks.public.root', storage_path('app/public/tenants/test-public-id')],
            ['filesystems.disks.public.url', config('app.url') . '/storage/tenants/test-public-id'],
            [
                'filesystems.disks.temp',
                [
                    'driver'     => 'local',
                    'root'       => storage_path('app/temp/tenants/test-public-id'),
                    'visibility' => 'private',
                ],
            ],
        ];

        $callIndex = 0;
        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets, &$callIndex) {
                $expected = $expectedSets[$callIndex];
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                $callIndex++;
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresS3WithAllOptions(): void
    {
        $tenant       = new Tenant();
        $tenant->id   = '789';
        $tenantConfig = [
            'aws_s3_bucket'      => 'tenant-bucket',
            'aws_s3_path_prefix' => 'custom/prefix',
            'aws_s3_key'         => 'AKIAIOSFODNN7EXAMPLE',
            'aws_s3_secret'      => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
            'aws_s3_region'      => 'eu-west-1',
            'aws_s3_url'         => 'https://cdn.example.com',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $s3Sets = [
            ['filesystems.disks.s3.bucket', 'tenant-bucket'],
            ['filesystems.disks.s3.path_prefix', 'custom/prefix'],
            ['filesystems.disks.s3.key', 'AKIAIOSFODNN7EXAMPLE'],
            ['filesystems.disks.s3.secret', 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY'],
            ['filesystems.disks.s3.region', 'eu-west-1'],
            ['filesystems.disks.s3.url', 'https://cdn.example.com'],
        ];

        $s3CallIndex = 0;
        $this->config->expects($this->atLeast(count($s3Sets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$s3Sets, &$s3CallIndex) {
                // Check if this is an S3 configuration
                if (str_starts_with($key, 'filesystems.disks.s3.')) {
                    $found = false;
                    foreach ($s3Sets as $index => $expected) {
                        if ($expected[0] === $key) {
                            $this->assertEquals($expected[1], $value);
                            $found = true;
                            break;
                        }
                    }
                    $this->assertTrue($found, "Unexpected S3 config key: $key");
                }
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresS3WithDefaultPathPrefix(): void
    {
        $tenant            = new Tenant();
        $tenant->id        = '999';
        $tenant->public_id = 'test-public-id';
        $tenantConfig      = [
            'aws_s3_bucket' => 'shared-bucket',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $this->config->expects($this->atLeast(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                if ($key === 'filesystems.disks.s3.bucket') {
                    $this->assertEquals('shared-bucket', $value);
                } elseif ($key === 'filesystems.disks.s3.path_prefix') {
                    $this->assertEquals('tenants/test-public-id', $value);
                }
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleDisablesTempIsolation(): void
    {
        $tenant       = new Tenant();
        $tenant->id   = '111';
        $tenantConfig = [
            'disable_temp_isolation' => true,
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        // Should not set temp disk when disabled
        $this->config->expects($this->any())
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                $this->assertNotEquals('filesystems.disks.temp', $key);
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

}
