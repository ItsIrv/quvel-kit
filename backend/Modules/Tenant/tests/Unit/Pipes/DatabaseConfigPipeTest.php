<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Log\LogManager;
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
    private ?Container $originalContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalContainer = Container::getInstance();

        $this->app       = $this->createPartialMock(Application::class, ['make', 'has', 'environment', 'instance', 'forgetInstance']);
        $this->config    = $this->createMock(ConfigRepository::class);
        $this->dbManager = $this->createMock(DatabaseManager::class);

        Container::setInstance($this->app);

        $this->app->method('has')
            ->willReturnMap([
                ['tenant.original_db_config', false],
                ['config', true],
            ]);

        $logger = $this->createMock(LogManager::class);
        $logger->expects($this->any())->method('debug');
        $logger->expects($this->any())->method('error');

        $this->app->method('make')
            ->willReturnMap([
                ['config', [], $this->config],
                ['log', [], $logger],
                [DatabaseManager::class, [], $this->dbManager],
            ]);

        $this->app->method('environment')
            ->willReturn(true);

        $this->app->method('instance')
            ->willReturnSelf();

        $this->app->method('forgetInstance')
            ->willReturnSelf();

        $this->pipe = new DatabaseConfigPipe();
    }

    public function testHandleStoresOriginalDatabaseConfig(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenant->name = 'Test Tenant';
        $tenantConfig = [];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['database.default', null, 'mysql'],
                ['database.connections', null, ['mysql' => ['host' => 'localhost']]],
                ['tenant.enable_tiers', false, false],
            ]);

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
        $this->assertSame($this->config, $result['config']);
        $this->assertSame($tenantConfig, $result['tenantConfig']);
    }

    public function testHandleSkipsConfigurationWhenTiersEnabledAndNoFeature(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->expects($this->once())
            ->method('hasFeature')
            ->with('database_isolation')
            ->willReturn(false);

        $tenantConfig = ['db_database' => 'tenant_db'];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['database.default', null, 'mysql'],
                ['database.connections', null, ['mysql' => ['host' => 'localhost']]],
                ['tenant.enable_tiers', false, true],
            ]);

        $this->config->expects($this->never())
            ->method('set');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleAppliesDatabaseConnection(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenant->name = 'Test Tenant';
        $tenantConfig = [
            'db_connection' => 'pgsql',
            'db_host'       => '192.168.1.100',
            'db_port'       => 5432,
            'db_database'   => 'tenant_db',
            'db_username'   => 'tenant_user',
            'db_password'   => 'secret',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['database.default', null, 'mysql'],
                ['database.connections', null, ['mysql' => ['host' => 'localhost']]],
                ['tenant.enable_tiers', false, false],
            ]);

        $this->config->expects($this->exactly(6))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                static $calls = [];
                $calls[] = [$key, $value];

                return null;
            });

        $this->dbManager->expects($this->exactly(2))
            ->method('getConnections')
            ->willReturn(['pgsql' => []]);

        $this->dbManager->expects($this->once())
            ->method('purge')
            ->with('pgsql');

        $this->dbManager->expects($this->once())
            ->method('setDefaultConnection')
            ->with('pgsql');

        $this->dbManager->expects($this->once())
            ->method('reconnect')
            ->with('pgsql');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleSkipsNonExistingConnection(): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenant->name = 'Test Tenant';
        $tenantConfig = [
            'db_host'     => '192.168.1.100',
            'db_database' => 'tenant_db',
        ];

        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['database.default', null, 'mysql'],
                ['database.connections', null, ['mysql' => ['host' => 'localhost']]],
                ['tenant.enable_tiers', false, false],
            ]);

        $this->dbManager->expects($this->once())
            ->method('getConnections')
            ->willReturn([]);

        $this->dbManager->expects($this->never())
            ->method('purge');

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    #[DataProvider('partialConfigProvider')]
    public function testHandleWithPartialConfig(array $tenantConfig, int $expectedSetCalls): void
    {
        $tenant       = $this->createMock(Tenant::class);
        $tenant->name = 'Test Tenant';

        $this->config->expects($this->any())
            ->method('get')
            ->willReturnMap([
                ['database.default', null, 'mysql'],
                ['database.connections', null, ['mysql' => ['host' => 'localhost']]],
                ['tenant.enable_tiers', false, false],
            ]);

        $this->config->expects($this->exactly($expectedSetCalls))
            ->method('set');

        $this->dbManager->expects($this->any())
            ->method('getConnections')
            ->willReturn([]);

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public static function partialConfigProvider(): array
    {
        return [
            'only host'         => [
                ['db_host' => '192.168.1.100'],
                1, // Only host gets set
            ],
            'host and database' => [
                ['db_host' => '192.168.1.100', 'db_database' => 'tenant_db'],
                2, // Host and database get set
            ],
            'only database'     => [
                ['db_database' => 'tenant_db'],
                1, // Only database gets set
            ],
            'empty config'      => [
                [],
                0, // Nothing gets set
            ],
        ];
    }

    public function testResetResourcesRestoresOriginalConfig(): void
    {
        Container::setInstance(null);
        $mockApp = $this->createPartialMock(Application::class, ['has', 'make', 'forgetInstance', 'environment', 'bound']);
        Container::setInstance($mockApp);

        $mockConfig    = $this->createMock(ConfigRepository::class);
        $mockDbManager = $this->createMock(DatabaseManager::class);
        $mockLogger    = $this->createMock(\Modules\Tenant\Logs\Pipes\DatabaseConfigPipeLogs::class);

        $originalConfig = [
            'default'     => 'mysql',
            'connections' => ['mysql' => ['host' => 'localhost']],
        ];

        $mockApp->expects($this->once())
            ->method('has')
            ->with('tenant.original_db_config')
            ->willReturn(true);

        $mockApp->method('bound')
            ->with(\Modules\Tenant\Logs\Pipes\DatabaseConfigPipeLogs::class)
            ->willReturn(true);

        $mockApp->method('make')
            ->willReturnMap([
                ['tenant.original_db_config', [], $originalConfig],
                ['config', [], $mockConfig],
                [DatabaseManager::class, [], $mockDbManager],
                [\Modules\Tenant\Logs\Pipes\DatabaseConfigPipeLogs::class, [], $mockLogger],
            ]);

        $mockApp->method('environment')
            ->willReturn(true);

        $mockApp->expects($this->once())
            ->method('forgetInstance')
            ->with('tenant.original_db_config');

        $mockConfig->expects($this->once())
            ->method('set')
            ->with([
                'database.default'     => 'mysql',
                'database.connections' => ['mysql' => ['host' => 'localhost']],
            ]);

        $mockDbManager->expects($this->once())->method('purge');
        $mockDbManager->expects($this->once())->method('setDefaultConnection')->with('mysql');
        $mockDbManager->expects($this->once())->method('reconnect')->with('mysql');

        $mockLogger->expects($this->once())->method('connectionReset')->with('mysql');

        DatabaseConfigPipe::resetResources();
    }

    public function testResetResourcesHandlesException(): void
    {
        Container::setInstance(null);
        $mockApp = $this->createPartialMock(Application::class, ['has', 'make', 'forgetInstance']);
        Container::setInstance($mockApp);

        $mockLogger = $this->createMock(LogManager::class);

        $mockApp->expects($this->once())
            ->method('has')
            ->with('tenant.original_db_config')
            ->willReturn(true);

        $mockApp->method('make')
            ->willReturnCallback(function ($abstract) use ($mockLogger) {
                if ($abstract === 'tenant.original_db_config') {
                    throw new \Exception('Test exception');
                }
                if ($abstract === 'log') {
                    return $mockLogger;
                }
                return null;
            });

        $mockLogger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to reset database connection'));

        DatabaseConfigPipe::resetResources();
    }

    public function testResetResourcesSkipsWhenNoOriginalConfig(): void
    {
        Container::setInstance(null);
        $mockApp = $this->createPartialMock(Application::class, ['has']);
        Container::setInstance($mockApp);

        $mockApp->expects($this->once())
            ->method('has')
            ->with('tenant.original_db_config')
            ->willReturn(false);

        DatabaseConfigPipe::resetResources();
    }

    public function testHandlesReturnsCorrectKeys(): void
    {
        $handles = $this->pipe->handles();

        $this->assertContains('db_connection', $handles);
        $this->assertContains('db_host', $handles);
        $this->assertContains('db_port', $handles);
        $this->assertContains('db_database', $handles);
        $this->assertContains('db_username', $handles);
        $this->assertContains('db_password', $handles);
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
