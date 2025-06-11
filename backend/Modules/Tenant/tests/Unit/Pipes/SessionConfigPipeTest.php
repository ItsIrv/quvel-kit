<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Session\SessionManager;
use Modules\Tenant\Logs\Pipes\SessionConfigPipeLogs;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\SessionConfigPipe;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SessionConfigPipe class.
 */
#[CoversClass(SessionConfigPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class SessionConfigPipeTest extends TestCase
{
    private SessionConfigPipe $pipe;
    private ConfigRepository|MockObject $config;
    private Application|MockObject $app;
    private SessionManager|MockObject $sessionManager;
    private SessionConfigPipeLogs|MockObject $logger;
    private ?Container $originalContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalContainer = Container::getInstance();

        $this->app = $this->createPartialMock(Application::class, [
            'make',
            'bound',
        ]);
        $this->config = $this->createMock(ConfigRepository::class);
        $this->sessionManager = $this->createMock(SessionManager::class);
        $this->logger = $this->createMock(SessionConfigPipeLogs::class);

        Container::setInstance($this->app);

        $this->app->method('make')
            ->willReturnMap([
                [SessionConfigPipeLogs::class, [], $this->logger],
                [SessionManager::class, [], $this->sessionManager],
            ]);

        $this->app->method('bound')
            ->willReturn(false);

        $this->pipe = new SessionConfigPipe($this->logger);
    }

    public function testHandleAppliesAllSessionConfig(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'test-tenant-123';

        $tenantConfig = [
            'session_driver' => 'redis',
            'session_lifetime' => 240,
            'session_encrypt' => true,
            'session_path' => '/app',
            'session_domain' => '.tenant.com',
            'session_cookie' => 'tenant_session',
        ];

        $expectedSets = [
            ['session.driver', 'redis'],
            ['session.lifetime', 240],
            ['session.encrypt', true],
            ['session.path', '/app'],
            ['session.domain', '.tenant.com'],
            ['session.cookie', 'tenant_session'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                return null;
            });

        $this->config->method('get')
            ->willReturnMap([
                ['session.cookie', null, 'old_cookie'],
                ['session.driver', null, 'file'],
            ]);

        $this->logger->expects($this->once())->method('driverChanged')->with('redis');
        $this->logger->expects($this->once())->method('lifetimeChanged')->with(240);
        $this->logger->expects($this->once())->method('encryptionChanged')->with(true);
        $this->logger->expects($this->once())->method('pathChanged')->with('/app');
        $this->logger->expects($this->once())->method('domainChanged')->with('.tenant.com');
        $this->logger->expects($this->once())->method('cookieNameChanged')->with('tenant_session', true);
        $this->logger->expects($this->once())->method('applyingChanges')->with(6);

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
        $this->assertSame($this->config, $result['config']);
        $this->assertSame($tenantConfig, $result['tenantConfig']);
    }

    public function testHandleExtractsSessionDomainFromAppUrl(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->willReturnCallback(function ($property) {
                if ($property === 'public_id') {
                    return 'test-tenant';
                } elseif ($property === 'domain') {
                    return 'fallback.domain.com';
                }
                return null;
            });

        $tenantConfig = [
            'app_url' => 'https://api.example.com',
            'session_driver' => 'file',
        ];

        $expectedSets = [
            ['session.driver', 'file'],
            ['session.domain', '.example.com'],
            ['session.cookie', 'tenant_test-tenant_session'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
            });

        $this->config->method('get')
            ->willReturn('old_value');

        $this->logger->expects($this->once())->method('driverChanged')->with('file');
        $this->logger->expects($this->once())->method('domainChanged')->with('.example.com');
        $this->logger->expects($this->once())->method('cookieNameChanged')->with('tenant_test-tenant_session', false);
        $this->logger->expects($this->once())->method('applyingChanges')->with(1);

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleUpdatesDatabaseConnectionForDatabaseDriver(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->willReturnCallback(function ($property) {
                if ($property === 'public_id') {
                    return 'test-tenant';
                } elseif ($property === 'domain') {
                    return 'test.domain.com';
                }
                return null;
            });

        $tenantConfig = [
            'session_driver' => 'database',
        ];

        $this->config->method('get')
            ->willReturnMap([
                ['session.cookie', null, 'old_cookie'],
                ['session.driver', null, 'database'],
                ['database.default', null, 'pgsql'],
            ]);

        $expectedSets = [
            ['session.driver', 'database'],
            ['session.domain', '.domain.com'],
            ['session.cookie', 'tenant_test-tenant_session'],
            ['session.connection', 'pgsql'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
            });

        $this->logger->expects($this->once())->method('driverChanged')->with('database');
        $this->logger->expects($this->once())->method('domainChanged')->with('.domain.com');
        $this->logger->expects($this->once())->method('cookieNameChanged');
        $this->logger->expects($this->once())->method('databaseConnectionChanged')->with('pgsql');
        $this->logger->expects($this->once())->method('applyingChanges')->with(1);

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }


    public function testResolveReturnsCorrectValuesAndVisibility(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'test-tenant-123';

        $tenantConfig = [
            'session_cookie' => 'custom_session_cookie',
        ];

        $result = $this->pipe->resolve($tenant, $tenantConfig);

        $this->assertArrayHasKey('values', $result);
        $this->assertArrayHasKey('visibility', $result);

        $expectedValues = [
            'sessionCookie' => 'custom_session_cookie',
        ];

        $expectedVisibility = [
            'sessionCookie' => 'protected',
        ];

        $this->assertEquals($expectedValues, $result['values']);
        $this->assertEquals($expectedVisibility, $result['visibility']);
    }

    public function testResolveGeneratesDefaultCookieWhenNotProvided(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->willReturnMap([
                ['public_id', 'test-tenant-456'],
                ['parent', null],
            ]);

        $tenantConfig = [];

        $result = $this->pipe->resolve($tenant, $tenantConfig);

        $expectedValues = [
            'sessionCookie' => 'tenant_test-tenant-456_session',
        ];

        $expectedVisibility = [
            'sessionCookie' => 'protected',
        ];

        $this->assertEquals($expectedValues, $result['values']);
        $this->assertEquals($expectedVisibility, $result['visibility']);
    }

    public function testResolveUsesParentTenantForCookie(): void
    {
        $parentTenant = $this->createMock(Tenant::class);
        $parentTenant->method('__get')
            ->with('public_id')
            ->willReturn('parent-tenant-123');

        $childTenant = $this->createMock(Tenant::class);
        $childTenant->method('__get')
            ->with('parent')
            ->willReturn($parentTenant);
        $childTenant->method('__isset')
            ->with('parent')
            ->willReturn(true);

        $tenantConfig = [];

        $result = $this->pipe->resolve($childTenant, $tenantConfig);

        $expectedValues = [
            'sessionCookie' => 'tenant_parent-tenant-123_session',
        ];

        $expectedVisibility = [
            'sessionCookie' => 'protected',
        ];

        $this->assertEquals($expectedValues, $result['values']);
        $this->assertEquals($expectedVisibility, $result['visibility']);
    }

    public function testHandleSetsDefaultCookieNameWhenNotProvided(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->method('__get')
            ->willReturnCallback(function ($property) {
                if ($property === 'public_id') {
                    return 'test-tenant-456';
                } elseif ($property === 'domain') {
                    return 'test.domain.com';
                }
                return null;
            });

        $tenantConfig = ['session_driver' => 'file'];

        $expectedSets = [
            ['session.driver', 'file'],
            ['session.domain', '.domain.com'],
            ['session.cookie', 'tenant_test-tenant-456_session'],
        ];

        $this->config->expects($this->exactly(count($expectedSets)))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$expectedSets) {
                $expected = array_shift($expectedSets);
                $this->assertEquals($expected[0], $key);
                $this->assertEquals($expected[1], $value);
                return null;
            });

        $this->config->method('get')->willReturn('file');

        $this->logger->expects($this->once())->method('driverChanged')->with('file');
        $this->logger->expects($this->once())->method('domainChanged')->with('.domain.com');
        $this->logger->expects($this->once())->method('cookieNameChanged')->with('tenant_test-tenant-456_session', false);
        $this->logger->expects($this->once())->method('applyingChanges')->with(1);

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    #[DataProvider('partialConfigProvider')]
    public function testHandleWithPartialConfig(array $tenantConfig, int $expectedSetCalls): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'test-tenant-123';

        $this->config->expects($this->exactly($expectedSetCalls))
            ->method('set');

        $this->config->method('get')->willReturn('file');

        if ($expectedSetCalls > 0) {
            $this->logger->expects($this->once())->method('applyingChanges');
        } else {
            $this->logger->expects($this->once())->method('noChangesToApply');
        }

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public static function partialConfigProvider(): array
    {
        return [
            'only driver' => [
                ['session_driver' => 'redis'],
                2, // driver + default cookie
            ],
            'only lifetime' => [
                ['session_lifetime' => 120],
                2, // lifetime + default cookie
            ],
            'custom cookie only' => [
                ['session_cookie' => 'custom_session'],
                1, // just the custom cookie
            ],
            'empty config still sets default cookie' => [
                [],
                1, // default cookie
            ],
        ];
    }

    public function testHandlesReturnsCorrectKeys(): void
    {
        $handles = $this->pipe->handles();

        $expectedKeys = [
            'session_driver',
            'session_lifetime',
            'session_encrypt',
            'session_path',
            'session_domain',
            'session_cookie',
        ];

        $this->assertEquals($expectedKeys, $handles);
    }

    public function testPriorityReturnsCorrectValue(): void
    {
        $priority = $this->pipe->priority();
        $this->assertEquals(10, $priority);
    }

    protected function tearDown(): void
    {
        if ($this->originalContainer) {
            Container::setInstance($this->originalContainer);
        }

        parent::tearDown();
    }
}
