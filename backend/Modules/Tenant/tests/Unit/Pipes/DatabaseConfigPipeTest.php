<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Modules\Tenant\Logs\Pipes\DatabaseConfigPipeLogs;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\DatabaseConfigPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the DatabaseConfigPipe class.
 */
#[CoversClass(DatabaseConfigPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class DatabaseConfigPipeTest extends TestCase
{
    private DatabaseConfigPipe $pipe;
    private ConfigRepository|MockObject $config;
    private Application|MockObject $app;
    private DatabaseManager|MockObject $dbManager;
    private DatabaseConfigPipeLogs|MockObject $logger;
    private ?Container $originalContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalContainer = Container::getInstance();

        $this->app = $this->createPartialMock(Application::class, [
            'make',
            'bound',
            'environment',
        ]);
        $this->config = $this->createMock(ConfigRepository::class);
        $this->dbManager = $this->createMock(DatabaseManager::class);
        $this->logger = $this->createMock(DatabaseConfigPipeLogs::class);

        Container::setInstance($this->app);

        // Configure app mock
        $this->app->method('make')
            ->willReturnMap([
                [DatabaseManager::class, [], $this->dbManager],
                [DatabaseConfigPipeLogs::class, [], $this->logger],
            ]);

        $this->app->method('bound')
            ->with(DatabaseConfigPipeLogs::class)
            ->willReturn(true);

        $this->app->method('environment')
            ->willReturn(true);

        // Make app() return logger mock when requested
        $originalApp = app();
        app()->instance(DatabaseConfigPipeLogs::class, $this->logger);

        $this->pipe = new DatabaseConfigPipe();
    }

    public function testHandleAppliesFullDatabaseConfiguration(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->with('name')
            ->willReturn('Test Tenant');

        $tenantConfig = [
            'db_connection' => 'pgsql',
            'db_host' => '192.168.1.100',
            'db_port' => 5432,
            'db_database' => 'tenant_db',
            'db_username' => 'tenant_user',
            'db_password' => 'secret_password',
        ];

        // Expect config sets in specific order
        $expectedSets = [
            ['database.default', 'pgsql'],
            ['database.connections.pgsql.host', '192.168.1.100'],
            ['database.connections.pgsql.port', 5432],
            ['database.connections.pgsql.database', 'tenant_db'],
            ['database.connections.pgsql.username', 'tenant_user'],
            ['database.connections.pgsql.password', 'secret_password'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
            });

        // Database manager expectations
        $this->dbManager->expects($this->exactly(2))
            ->method('getConnections')
            ->willReturn(['pgsql' => 'connection']);

        $this->dbManager->expects($this->once())
            ->method('purge')
            ->with('pgsql');

        $this->dbManager->expects($this->once())
            ->method('setDefaultConnection')
            ->with('pgsql');

        $this->dbManager->expects($this->once())
            ->method('reconnect')
            ->with('pgsql');

        // Logger expectations
        $this->logger->expects($this->once())
            ->method('connectionSwitched')
            ->with('pgsql', 'Test Tenant');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
        $this->assertSame($this->config, $result['config']);
        $this->assertSame($tenantConfig, $result['tenantConfig']);
    }

    public function testHandleSkipsConfigurationWhenNoDbOverrides(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'some_other_config' => 'value',
        ];

        // Should not set any config
        $this->config->expects($this->never())->method('set');

        // Should not interact with database manager
        $this->dbManager->expects($this->never())->method('getConnections');
        $this->dbManager->expects($this->never())->method('purge');
        $this->dbManager->expects($this->never())->method('setDefaultConnection');
        $this->dbManager->expects($this->never())->method('reconnect');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleUsesDefaultConnectionWhenNotSpecified(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->with('name')
            ->willReturn('Test Tenant');

        $tenantConfig = [
            'db_host' => 'new-host.com',
            'db_database' => 'new_database',
        ];

        // Should use mysql as default connection
        $this->config->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                static $callCount = 0;
                $callCount++;
                if ($callCount === 1) {
                    $this->assertEquals('database.connections.mysql.host', $key);
                    $this->assertEquals('new-host.com', $value);
                } elseif ($callCount === 2) {
                    $this->assertEquals('database.connections.mysql.database', $key);
                    $this->assertEquals('new_database', $value);
                }
            });

        $this->dbManager->expects($this->once())
            ->method('getConnections')
            ->willReturn([]);

        $this->dbManager->expects($this->never())
            ->method('purge');

        $this->dbManager->expects($this->once())
            ->method('setDefaultConnection')
            ->with('mysql');

        $this->dbManager->expects($this->once())
            ->method('reconnect')
            ->with('mysql');

        $this->logger->expects($this->once())
            ->method('connectionSwitched')
            ->with('mysql', 'Test Tenant');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    #[DataProvider('partialConfigProvider')]
    public function testHandleWithPartialConfig(array $tenantConfig, array $expectedSets): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->with('name')
            ->willReturn('Test Tenant');

        if (!empty($expectedSets)) {
            $this->config->expects($this->exactly(count($expectedSets)))
                ->method('set')
                ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                    $expected = array_shift($expectedSets);
                    $this->assertEquals($expected[0], $key);
                    $this->assertEquals($expected[1], $value);
                });

            $this->dbManager->expects($this->once())->method('getConnections')->willReturn([]);
            $this->dbManager->expects($this->once())->method('setDefaultConnection');
            $this->dbManager->expects($this->once())->method('reconnect');
            $this->logger->expects($this->once())->method('connectionSwitched');
        } else {
            $this->config->expects($this->never())->method('set');
            $this->dbManager->expects($this->never())->method('setDefaultConnection');
        }

        $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
    }

    public static function partialConfigProvider(): array
    {
        return [
            'only host' => [
                ['db_host' => '10.0.0.1'],
                [['database.connections.mysql.host', '10.0.0.1']],
            ],
            'host and database' => [
                ['db_host' => '10.0.0.1', 'db_database' => 'custom_db'],
                [
                    ['database.connections.mysql.host', '10.0.0.1'],
                    ['database.connections.mysql.database', 'custom_db'],
                ],
            ],
            'only database' => [
                ['db_database' => 'custom_db'],
                [['database.connections.mysql.database', 'custom_db']],
            ],
            'empty config' => [
                [],
                [],
            ],
        ];
    }

    public function testResolveReturnsEmptyArray(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenantConfig = [
            'db_connection' => 'pgsql',
            'db_database' => 'tenant_db',
        ];

        $result = $this->pipe->resolve($tenant, $tenantConfig);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testHandlesReturnsCorrectKeys(): void
    {
        $handles = $this->pipe->handles();

        $expectedKeys = [
            'db_connection',
            'db_host',
            'db_port',
            'db_database',
            'db_username',
            'db_password',
        ];

        $this->assertEquals($expectedKeys, $handles);
    }

    public function testPriorityReturnsCorrectValue(): void
    {
        $priority = $this->pipe->priority();
        $this->assertEquals(90, $priority);
    }

    protected function tearDown(): void
    {
        if ($this->originalContainer) {
            Container::setInstance($this->originalContainer);
        }

        parent::tearDown();
    }
}
