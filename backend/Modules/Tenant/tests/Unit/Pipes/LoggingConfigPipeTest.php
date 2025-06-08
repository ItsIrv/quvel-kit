<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\LoggingConfigPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

/**
 * Unit tests for the LoggingConfigPipe class.
 */
#[CoversClass(LoggingConfigPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class LoggingConfigPipeTest extends TestCase
{
    private LoggingConfigPipe $pipe;
    private ConfigRepository|MockObject $config;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createMock(ConfigRepository::class);
        $this->pipe   = new LoggingConfigPipe();
    }

    public function testHandleAppliesDefaultLogChannel(): void
    {
        $tenant       = new Tenant();
        $tenant->id   = '123';
        $tenantConfig = ['log_channel' => 'daily'];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $this->config->expects($this->atLeast(3))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                if ($key === 'logging.default') {
                    $this->assertEquals('daily', $value);
                }
                // Also accepts the default path settings
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleAppliesCustomLogPaths(): void
    {
        $tenant       = new Tenant();
        $tenant->id   = '123';
        $tenantConfig = [
            'log_single_path' => '/custom/single.log',
            'log_daily_path'  => '/custom/daily.log',
            'log_daily_days'  => 30,
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['logging.channels.single.path', '/custom/single.log'],
            ['logging.channels.daily.path', '/custom/daily.log'],
            ['logging.channels.daily.days', 30],
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

    public function testHandleAppliesDefaultTenantIsolation(): void
    {
        $tenant            = new Tenant();
        $tenant->id        = '456';
        $tenant->public_id = 'test-public-id';
        $tenantConfig      = [];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['logging.channels.single.path', storage_path('logs/tenants/test-public-id/laravel.log')],
            ['logging.channels.daily.path', storage_path('logs/tenants/test-public-id/laravel.log')],
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

    public function testHandleAppliesLogLevel(): void
    {
        $tenant       = new Tenant();
        $tenant->id   = '123';
        $tenantConfig = ['log_level' => 'debug'];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['logging.channels.single.level', 'debug'],
            ['logging.channels.daily.level', 'debug'],
            ['logging.channels.slack.level', 'debug'],
            ['logging.channels.stderr.level', 'debug'],
        ];

        $levelSets = 0;
        $this->config->expects($this->atLeast(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets, &$levelSets) {
                foreach ($expectedSets as $expected) {
                    if ($expected[0] === $key) {
                        $this->assertEquals($expected[1], $value);
                        $levelSets++;
                        break;
                    }
                }
                return null;
            });

        $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertEquals(count($expectedSets), $levelSets);
    }

    public function testHandleConfiguresSlackLogging(): void
    {
        $tenant       = new Tenant();
        $tenant->id   = '123';
        $tenantConfig = [
            'log_slack_webhook_url' => 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX',
            'log_slack_channel'     => '#alerts',
            'log_slack_username'    => 'Tenant Logger',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $expectedSets = [
            ['logging.channels.slack.url', 'https://hooks.slack.com/services/T00000000/B00000000/XXXXXXXXXXXXXXXXXXXXXXXX'],
            ['logging.channels.slack.channel', '#alerts'],
            ['logging.channels.slack.username', 'Tenant Logger'],
        ];

        $slackSets = 0;
        $this->config->expects($this->atLeast(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets, &$slackSets) {
                foreach ($expectedSets as $expected) {
                    if ($expected[0] === $key) {
                        $this->assertEquals($expected[1], $value);
                        $slackSets++;
                        break;
                    }
                }
                return null;
            });

        $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertEquals(count($expectedSets), $slackSets);
    }

    public function testHandleConfiguresSentryLogging(): void
    {
        $tenant       = new Tenant();
        $tenant->id   = '123';
        $tenantConfig = [
            'sentry_dsn'         => 'https://examplePublicKey@o0.ingest.sentry.io/0',
            'sentry_level'       => 'warning',
            'sentry_environment' => 'production',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $this->config->expects($this->atLeast(3))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                if ($key === 'logging.channels.sentry') {
                    $this->assertEquals([
                        'driver' => 'sentry',
                        'level'  => 'warning',
                        'bubble' => true,
                    ], $value);
                } elseif ($key === 'services.sentry.dsn') {
                    $this->assertEquals('https://examplePublicKey@o0.ingest.sentry.io/0', $value);
                } elseif ($key === 'services.sentry.environment') {
                    $this->assertEquals('production', $value);
                }
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresCustomLogChannel(): void
    {
        $tenant       = new Tenant();
        $tenant->id   = '789';
        $tenantConfig = [
            'log_custom_driver' => 'daily',
            'log_custom_path'   => '/var/log/tenant.log',
            'log_custom_level'  => 'error',
            'log_custom_days'   => 7,
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $this->config->expects($this->atLeast(1))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                if ($key === 'logging.channels.tenant') {
                    $this->assertEquals([
                        'driver' => 'daily',
                        'path'   => '/var/log/tenant.log',
                        'level'  => 'error',
                        'days'   => 7,
                    ], $value);
                }
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleConfiguresStackChannels(): void
    {
        $tenant       = new Tenant();
        $tenant->id   = '123';
        $tenantConfig = [
            'log_stack_channels' => ['daily', 'slack', 'sentry'],
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $this->config->expects($this->atLeast(1))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                if ($key === 'logging.channels.stack.channels') {
                    $this->assertEquals(['daily', 'slack', 'sentry'], $value);
                }
                return null;
            });

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    #[DataProvider('partialConfigProvider')]
    public function testHandleWithPartialConfig(array $tenantConfig, array $expectedKeys): void
    {
        $tenant     = new Tenant();
        $tenant->id = '123';

        $this->config->expects($this->any())
            ->method('get')
            ->willReturn([]);

        $setCalls = [];
        $this->config->expects($this->any())
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$setCalls) {
                $setCalls[] = $key;
                return null;
            });

        $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        foreach ($expectedKeys as $expectedKey) {
            $found = false;
            foreach ($setCalls as $setKey) {
                if (str_contains($setKey, $expectedKey)) {
                    $found = true;
                    break;
                }
            }
            $this->assertTrue($found, "Expected key containing '$expectedKey' not found in set calls");
        }
    }

    public static function partialConfigProvider(): array
    {
        return [
            'only deprecations channel'    => [
                ['log_deprecations_channel' => 'slack'],
                ['deprecations'],
            ],
            'sentry with defaults'         => [
                ['sentry_dsn' => 'https://sentry.io/dsn'],
                ['sentry', 'services.sentry.dsn'],
            ],
            'custom channel with defaults' => [
                ['log_custom_driver' => 'single'],
                ['tenant'],
            ],
        ];
    }

    public function testHandlesReturnsCorrectKeys(): void
    {
        $handles = $this->pipe->handles();

        $expectedKeys = [
            'log_channel',
            'log_deprecations_channel',
            'log_single_path',
            'log_daily_path',
            'log_daily_days',
            'log_level',
            'log_slack_webhook_url',
            'log_slack_channel',
            'log_slack_username',
            'sentry_dsn',
            'sentry_level',
            'sentry_environment',
            'log_custom_driver',
            'log_custom_path',
            'log_custom_level',
            'log_custom_days',
            'log_stack_channels',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertContains($key, $handles);
        }
        $this->assertCount(count($expectedKeys), $handles);
    }

    public function testPriorityReturnsCorrectValue(): void
    {
        $priority = $this->pipe->priority();

        $this->assertEquals(40, $priority);
    }
}
