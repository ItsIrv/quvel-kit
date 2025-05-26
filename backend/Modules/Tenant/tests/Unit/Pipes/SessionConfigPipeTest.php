<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Application;
use Illuminate\Log\LogManager;
use Illuminate\Session\SessionManager;
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
    private ?Container $originalContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->originalContainer = Container::getInstance();
        
        $this->app = $this->createPartialMock(Application::class, [
            'make', 'bound', 'environment', 'extend', 'forgetInstance'
        ]);
        $this->config = $this->createMock(ConfigRepository::class);
        $this->sessionManager = $this->createMock(SessionManager::class);
        
        Container::setInstance($this->app);
        
        $logger = $this->createMock(LogManager::class);
        $logger->expects($this->any())->method('debug');
        $logger->expects($this->any())->method('error');
            
        $this->app->method('make')
            ->willReturnMap([
                ['config', [], $this->config],
                ['log', [], $logger],
                [SessionManager::class, [], $this->sessionManager]
            ]);
            
        $this->app->method('environment')
            ->willReturn(true);
            
        $this->pipe = new SessionConfigPipe();
    }

    public function testHandleAppliesAllSessionConfig(): void
    {
        $tenant = new Tenant();
        $tenant->id = '123';
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
        
        $this->config->method('get')->willReturn('file');
        
        $this->app->expects($this->once())
            ->method('bound')
            ->with(SessionManager::class)
            ->willReturn(true);
            
        $this->app->expects($this->once())
            ->method('extend')
            ->with(SessionManager::class);
            
        $callIndex = 0;
        $this->app->expects($this->exactly(2))
            ->method('forgetInstance')
            ->willReturnCallback(function ($arg) use (&$callIndex) {
                $expected = [SessionManager::class, Session::class];
                $this->assertEquals($expected[$callIndex], $arg);
                $callIndex++;
                return null;
            });
        
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
        
        $this->assertSame($tenant, $result['tenant']);
        $this->assertSame($this->config, $result['config']);
        $this->assertSame($tenantConfig, $result['tenantConfig']);
    }

    public function testHandleSetsDefaultCookieNameWhenNotProvided(): void
    {
        $tenant = new Tenant();
        $tenant->id = '456';
        $tenantConfig = ['session_driver' => 'file'];
        
        $callIndex = 0;
        $this->config->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$callIndex) {
                $expected = [
                    ['session.driver', 'file'],
                    ['session.cookie', 'tenant_456_session']
                ];
                $this->assertEquals($expected[$callIndex][0], $key);
                $this->assertEquals($expected[$callIndex][1], $value);
                $callIndex++;
                return null;
            });
        
        $this->config->method('get')->willReturn('file');
        
        $this->app->expects($this->once())
            ->method('bound')
            ->with(SessionManager::class)
            ->willReturn(true);
            
        $this->app->expects($this->once())->method('extend');
        $this->app->expects($this->exactly(2))->method('forgetInstance');
        
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
        
        $this->assertSame($tenant, $result['tenant']);
    }

    public function testHandleSetsSessionConnectionForDatabaseDriver(): void
    {
        $tenant = new Tenant();
        $tenant->id = '789';
        $tenantConfig = ['session_driver' => 'database'];
        
        $callIndex = 0;
        $this->config->expects($this->exactly(3))
            ->method('set')
            ->willReturnCallback(function ($key, $value) use (&$callIndex) {
                $expected = [
                    ['session.driver', 'database'],
                    ['session.cookie', 'tenant_789_session'],
                    ['session.connection', 'mysql']
                ];
                $this->assertEquals($expected[$callIndex][0], $key);
                $this->assertEquals($expected[$callIndex][1], $value);
                $callIndex++;
                return null;
            });
        
        $this->config->method('get')
            ->willReturnMap([
                ['session.driver', null, 'database'],
                ['database.default', null, 'mysql']
            ]);
        
        $this->app->expects($this->once())
            ->method('bound')
            ->with(SessionManager::class)
            ->willReturn(true);
            
        $this->app->expects($this->once())->method('extend');
        $this->app->expects($this->exactly(2))->method('forgetInstance');
        
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
        
        $this->assertSame($tenant, $result['tenant']);
    }

    #[DataProvider('partialConfigProvider')]
    public function testHandleWithPartialConfig(array $tenantConfig, int $expectedSetCalls): void
    {
        $tenant = new Tenant();
        $tenant->id = '123';
        
        $this->config->expects($this->exactly($expectedSetCalls))
            ->method('set');
        
        $this->config->method('get')->willReturn('file');
        
        if ($expectedSetCalls > 0) {
            $this->app->expects($this->once())
                ->method('bound')
                ->with(SessionManager::class)
                ->willReturn(true);
                
            $this->app->expects($this->once())->method('extend');
            $this->app->expects($this->exactly(2))->method('forgetInstance');
        } else {
            $this->app->expects($this->never())->method('bound');
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

    public function testRebindSessionManagerHandlesException(): void
    {
        $tenant = new Tenant();
        $tenant->id = '123';
        $tenantConfig = ['session_driver' => 'file'];
        
        $this->config->expects($this->any())->method('set');
        $this->config->method('get')->willReturn('file');
        
        $this->app->expects($this->once())
            ->method('bound')
            ->with(SessionManager::class)
            ->willReturn(true);
            
        $this->app->expects($this->once())
            ->method('extend')
            ->willThrowException(new \Exception('Extend failed'));
        
        // Should not throw exception, but handle it gracefully
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
        
        $this->assertSame($tenant, $result['tenant']);
    }

    public function testRebindSessionManagerSkipsWhenNotBound(): void
    {
        $tenant = new Tenant();
        $tenant->id = '123';
        $tenantConfig = ['session_driver' => 'file'];
        
        $this->config->expects($this->any())->method('set');
        $this->config->method('get')->willReturn('file');
        
        $this->app->expects($this->once())
            ->method('bound')
            ->with(SessionManager::class)
            ->willReturn(false);
            
        $this->app->expects($this->never())->method('extend');
        
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
        
        $this->assertSame($tenant, $result['tenant']);
    }

    public function testResetResourcesRebindsSessionManager(): void
    {
        Container::setInstance(null);
        $mockApp = $this->createPartialMock(Application::class, [
            'bound', 'make', 'extend', 'forgetInstance', 'environment'
        ]);
        Container::setInstance($mockApp);
        
        $mockLogger = $this->createMock(LogManager::class);
        
        $mockApp->expects($this->once())
            ->method('bound')
            ->with(SessionManager::class)
            ->willReturn(true);
            
        $mockApp->method('make')
            ->willReturnMap([
                ['log', [], $mockLogger]
            ]);
            
        $mockApp->method('environment')->willReturn(true);
        
        $mockApp->expects($this->once())
            ->method('extend')
            ->with(SessionManager::class);
            
        $callIndex = 0;
        $mockApp->expects($this->exactly(2))
            ->method('forgetInstance')
            ->willReturnCallback(function ($arg) use (&$callIndex) {
                $expected = [SessionManager::class, Session::class];
                $this->assertEquals($expected[$callIndex], $arg);
                $callIndex++;
                return null;
            });
        
        $mockLogger->expects($this->once())->method('debug');
        
        SessionConfigPipe::resetResources();
    }

    public function testResetResourcesHandlesException(): void
    {
        Container::setInstance(null);
        $mockApp = $this->createPartialMock(Application::class, [
            'bound', 'make', 'extend'
        ]);
        Container::setInstance($mockApp);
        
        $mockLogger = $this->createMock(LogManager::class);
        
        $mockApp->expects($this->once())
            ->method('bound')
            ->with(SessionManager::class)
            ->willReturn(true);
            
        $mockApp->method('make')
            ->willReturnMap([
                ['log', [], $mockLogger]
            ]);
            
        $mockApp->expects($this->once())
            ->method('extend')
            ->willThrowException(new \Exception('Test exception'));
        
        $mockLogger->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to reset session manager'));
        
        SessionConfigPipe::resetResources();
    }

    public function testResetResourcesSkipsWhenNotBound(): void
    {
        Container::setInstance(null);
        $mockApp = $this->createPartialMock(Application::class, ['bound']);
        Container::setInstance($mockApp);
        
        $mockApp->expects($this->once())
            ->method('bound')
            ->with(SessionManager::class)
            ->willReturn(false);
        
        SessionConfigPipe::resetResources();
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
        
        $this->assertEquals(83, $priority);
    }

    protected function tearDown(): void
    {
        if ($this->originalContainer) {
            Container::setInstance($this->originalContainer);
        }
        
        parent::tearDown();
    }
}