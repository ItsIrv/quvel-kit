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

        $this->app            = $this->createPartialMock(Application::class, [
            'make',
            'bound',
        ]);
        $this->config         = $this->createMock(ConfigRepository::class);
        $this->sessionManager = $this->createMock(SessionManager::class);

        Container::setInstance($this->app);

        // Create a mock logger for SessionConfigPipe
        $this->logger = $this->createMock(SessionConfigPipeLogs::class);

        $this->app->method('make')
            ->willReturnMap([
                [SessionConfigPipeLogs::class, [], $this->logger],
            ]);

        $this->pipe = new SessionConfigPipe($this->logger);
    }

    public function testHandleAppliesAllSessionConfig(): void
    {
        $tenant            = new Tenant();
        $tenant->id        = '123';
        $tenant->public_id = 'test-tenant-123';
        $tenantConfig      = [
            'session_driver'   => 'redis',
            'session_lifetime' => 240,
            'session_encrypt'  => true,
            'session_path'     => '/app',
            'session_domain'   => '.tenant.com',
            'session_cookie'   => 'tenant_session',
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

        // Logger expectations
        $this->logger->expects($this->once())->method('driverChanged')->with('redis');
        $this->logger->expects($this->once())->method('lifetimeChanged')->with(240);
        $this->logger->expects($this->once())->method('encryptionChanged')->with(true);
        $this->logger->expects($this->once())->method('pathChanged')->with('/app');
        $this->logger->expects($this->once())->method('domainChanged')->with('.tenant.com');
        $this->logger->expects($this->once())->method('cookieNameChanged')->with('tenant_session', true);
        $this->logger->expects($this->once())->method('debug')->with(
            'Session cookie name changed',
            ['old_cookie' => 'old_cookie', 'new_cookie' => 'tenant_session'],
        );
        $this->logger->expects($this->once())->method('applyingChanges')->with(6);

        // No session manager interaction when session is bound but method_exists checks fail
        $this->app->expects($this->once())
            ->method('bound')
            ->with(SessionManager::class)
            ->willReturn(false);

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
        $this->assertSame($this->config, $result['config']);
        $this->assertSame($tenantConfig, $result['tenantConfig']);
    }

    public function testHandleSetsDefaultCookieNameWhenNotProvided(): void
    {
        $tenant            = new Tenant();
        $tenant->id        = '456';
        $tenant->public_id = 'test-tenant-456';
        $tenantConfig      = ['session_driver' => 'file'];

        $expectedSets = [
            ['session.driver', 'file'],
            ['session.cookie', 'tenant_456_session'],
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

        // Logger expectations
        $this->logger->expects($this->once())->method('driverChanged')->with('file');
        $this->logger->expects($this->once())->method('cookieNameChanged')->with('tenant_456_session', false);
        $this->logger->expects($this->once())->method('applyingChanges')->with(1);

        // No session manager interaction when session not bound
        $this->app->expects($this->once())
            ->method('bound')
            ->with(SessionManager::class)
            ->willReturn(false);

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleSetsSessionConnectionForDatabaseDriver(): void
    {
        $tenant            = new Tenant();
        $tenant->id        = '789';
        $tenant->public_id = 'test-tenant-789';
        $tenantConfig      = ['session_driver' => 'database'];

        $expectedSets = [
            ['session.driver', 'database'],
            ['session.cookie', 'tenant_789_session'],
            ['session.connection', 'mysql'],
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
                ['session.driver', null, 'database'],
                ['database.default', null, 'mysql'],
                ['session.cookie', null, 'old_cookie'],
            ]);

        // Logger expectations
        $this->logger->expects($this->once())->method('driverChanged')->with('database');
        $this->logger->expects($this->once())->method('cookieNameChanged')->with('tenant_789_session', false);
        $this->logger->expects($this->once())->method('databaseConnectionChanged')->with('mysql');
        $this->logger->expects($this->once())->method('applyingChanges')->with(1);

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    #[DataProvider('partialConfigProvider')]
    public function testHandleWithPartialConfig(array $tenantConfig, int $expectedSetCalls): void
    {
        $tenant            = new Tenant();
        $tenant->id        = '123';
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
            'only driver'                            => [
                ['session_driver' => 'redis'],
                2, // driver + default cookie
            ],
            'only lifetime'                          => [
                ['session_lifetime' => 120],
                2, // lifetime + default cookie
            ],
            'custom cookie only'                     => [
                ['session_cookie' => 'custom_session'],
                1, // just the custom cookie
            ],
            'empty config still sets default cookie' => [
                [],
                1, // default cookie
            ],
        ];
    }

    public function testNoChangesWhenAllConfigMatchesExisting(): void
    {
        $tenant            = new Tenant();
        $tenant->id        = '123';
        $tenant->public_id = 'test-tenant-123';
        $tenantConfig      = [];

        // Only the default cookie should be set
        $this->config->expects($this->once())
            ->method('set')
            ->with('session.cookie', 'tenant_123_session');

        $this->config->method('get')->willReturn('tenant_123_session');

        $this->logger->expects($this->once())->method('cookieNameChanged')->with('tenant_123_session', false);
        $this->logger->expects($this->once())->method('applyingChanges')->with(0);

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
    }

    public function testUpdatesSessionDriverWhenSessionExists(): void
    {
        $tenant            = new Tenant();
        $tenant->id        = '123';
        $tenant->public_id = 'test-tenant-123';
        $tenantConfig      = ['session_cookie' => 'my_custom_cookie'];

        $this->config->expects($this->once())
            ->method('set')
            ->with('session.cookie', 'my_custom_cookie');

        $this->config->method('get')
            ->willReturnMap([
                ['session.cookie', null, 'old_cookie'],
            ]);

        // No session manager interaction when session is bound but method_exists checks fail
        $this->app->expects($this->once())
            ->method('bound')
            ->with(SessionManager::class)
            ->willReturn(false);

        $this->logger->expects($this->once())->method('cookieNameChanged')->with('my_custom_cookie', true);
        $this->logger->expects($this->once())->method('applyingChanges')->with(1);
        $this->logger->expects($this->once())->method('debug')
            ->with('Session cookie name changed', ['old_cookie' => 'old_cookie', 'new_cookie' => 'my_custom_cookie']);

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
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

        foreach ($expectedKeys as $key) {
            $this->assertContains($key, $handles);
        }
        $this->assertCount(count($expectedKeys), $handles);
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
