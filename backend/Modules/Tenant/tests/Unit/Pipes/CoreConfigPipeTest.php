<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Application;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Modules\Tenant\Logs\Pipes\CoreConfigPipeLogs;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Pipes\CoreConfigPipe;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Unit tests for the CoreConfigPipe class.
 */
#[CoversClass(CoreConfigPipe::class)]
#[Group('tenant-module')]
#[Group('tenant-pipes')]
class CoreConfigPipeTest extends TestCase
{
    /**
     * @var CoreConfigPipe The pipe instance being tested
     */
    private CoreConfigPipe $pipe;

    /**
     * @var ConfigRepository&MockObject The mocked config repository
     */
    private ConfigRepository|MockObject $config;

    /**
     * @var Application&MockObject The mocked application container
     */
    private Application|MockObject $app;

    /**
     * @var Container|null Original application instance
     */
    private ?Container $originalContainer = null;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Store the original container instance
        $this->originalContainer = Container::getInstance();

        // Create mock app container and config repository
        $this->app = $this->createPartialMock(Application::class, [
            'make',
            'bound',
            'environment',
            'instance',
            'forgetInstance',
            'extend',
            'offsetGet'
        ]);
        $this->config = $this->createMock(ConfigRepository::class);

        // Set Container instance
        Container::setInstance($this->app);

        // Mock context repository for Context facade
        $contextRepo = $this->createMock(\Illuminate\Log\Context\Repository::class);
        $contextRepo->expects($this->any())
            ->method('add')
            ->willReturn(null);

        // Configure app container
        $this->app->method('bound')
            ->willReturnMap([
                [CoreConfigPipeLogs::class, false],
                [UrlGenerator::class, true],
                [Translator::class, true],
                ['log.context', true],
            ]);

        $this->app->method('make')
            ->willReturnMap([
                ['log.context', [], $contextRepo],
                [UrlGenerator::class, [], $this->createMock(UrlGenerator::class)],
            ]);

        $this->app->method('offsetGet')
            ->with('log.context')
            ->willReturn($contextRepo);

        $this->app->method('environment')
            ->willReturn(false);

        // Mock Context facade
        Context::swap($contextRepo);

        // Create the pipe
        $this->pipe = new CoreConfigPipe();
    }

    /**
     * Test that the handle method sets app configuration values.
     */
    public function testHandleSetsAppConfiguration(): void
    {
        // Arrange
        $tenant            = $this->createMock(Tenant::class);
        $tenant->public_id = 'tenant-123';

        $tenantConfig = [
            'app_name'            => 'Test App',
            'app_env'             => 'testing',
            'app_key'             => 'base64:test-key',
            'app_debug'           => true,
            'app_url'             => 'https://test.example.com',
            'app_timezone'        => 'America/New_York',
            'app_locale'          => 'en',
            'app_fallback_locale' => 'en',
        ];

        // Set up config expectations - app config + CORS origins
        $this->config->expects($this->exactly(9)) // 8 app configs + 1 CORS
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                $allowedKeys = [
                    'app.name', 'app.env', 'app.key', 'app.debug', 'app.url', 
                    'app.timezone', 'app.locale', 'app.fallback_locale', 'cors.allowed_origins'
                ];
                $this->assertContains($key, $allowedKeys);
                
                if ($key === 'cors.allowed_origins') {
                    $this->assertContains('https://test.example.com', $value);
                }
            });

        // Mock config get for refreshes (URL, timezone, locale)
        $this->config->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'app.url' => 'https://test.example.com',
                    'app.timezone' => 'America/New_York',
                    'app.locale' => 'en',
                    default => null,
                };
            });

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
        $this->assertSame($this->config, $result['config']);
        $this->assertSame($tenantConfig, $result['tenantConfig']);
    }

    /**
     * Test that the handle method sets frontend configuration values.
     */
    public function testHandleSetsFrontendConfiguration(): void
    {
        // Arrange
        $tenant            = $this->createMock(Tenant::class);
        $tenant->public_id = 'tenant-123';

        $tenantConfig = [
            'frontend_url'     => 'https://frontend.example.com',
            'internal_api_url' => 'https://api.example.com',
            'capacitor_scheme' => 'testapp',
        ];

        // Set up config expectations - frontend config + CORS origins
        $this->config->expects($this->exactly(4)) // 3 frontend configs + 1 CORS
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                $allowedKeys = [
                    'frontend.url', 'frontend.internal_api_url', 'frontend.capacitor_scheme', 'cors.allowed_origins'
                ];
                $this->assertContains($key, $allowedKeys);
                
                if ($key === 'cors.allowed_origins') {
                    $this->assertContains('https://frontend.example.com', $value);
                }
            });

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
    }

    /**
     * Test that the handle method sets CORS configuration.
     */
    public function testHandleSetsCorsConfiguration(): void
    {
        // Arrange
        $tenant            = $this->createMock(Tenant::class);
        $tenant->public_id = 'tenant-123';

        $tenantConfig = [
            'app_url'      => 'https://app.example.com',
            'frontend_url' => 'https://frontend.example.com',
        ];

        // Set up config expectations - 2 app configs + 1 CORS
        $this->config->expects($this->exactly(3))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                $allowedKeys = ['app.url', 'frontend.url', 'cors.allowed_origins'];
                $this->assertContains($key, $allowedKeys);
                
                if ($key === 'cors.allowed_origins') {
                    $this->assertContains('https://app.example.com', $value);
                    $this->assertContains('https://frontend.example.com', $value);
                }
            });

        // Act
        $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
    }

    /**
     * Test that the handle method sets Pusher configuration.
     */
    public function testHandleSetsPusherConfiguration(): void
    {
        // Arrange
        $tenant            = $this->createMock(Tenant::class);
        $tenant->public_id = 'tenant-123';

        $tenantConfig = [
            'pusher_app_key'     => 'test-key',
            'pusher_app_secret'  => 'test-secret',
            'pusher_app_id'      => 'test-app-id',
            'pusher_app_cluster' => 'us-east-1',
        ];

        // Set up config expectations
        $this->config->expects($this->exactly(4))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                $allowedKeys = [
                    'broadcasting.connections.pusher.key',
                    'broadcasting.connections.pusher.secret',
                    'broadcasting.connections.pusher.app_id',
                    'broadcasting.connections.pusher.options.cluster'
                ];
                $this->assertContains($key, $allowedKeys);
            });

        // Act
        $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
    }

    /**
     * Test that the handle method refreshes URL generator when app_url changes.
     */
    public function testHandleRefreshesUrlGeneratorWhenUrlChanges(): void
    {
        // Arrange
        $tenant            = $this->createMock(Tenant::class);
        $tenant->public_id = 'tenant-123';

        $tenantConfig = ['app_url' => 'https://test.example.com'];

        $urlGenerator = $this->createMock(UrlGenerator::class);
        $urlGenerator->expects($this->once())
            ->method('forceRootUrl')
            ->with('https://test.example.com');

        $contextRepo = $this->createMock(\Illuminate\Log\Context\Repository::class);
        $contextRepo->expects($this->any())
            ->method('add')
            ->willReturn(null);

        // Create a new app mock for this specific test
        $testApp = $this->createPartialMock(Application::class, [
            'make',
            'bound',
            'environment',
            'instance',
            'forgetInstance',
            'extend',
            'offsetGet'
        ]);
        
        // Override the container instance for this test
        Container::setInstance($testApp);
        
        $testApp->method('make')
            ->willReturnCallback(function ($abstract) use ($urlGenerator, $contextRepo) {
                if ($abstract === UrlGenerator::class) {
                    return $urlGenerator;
                }
                if ($abstract === 'log.context') {
                    return $contextRepo;
                }
                return null;
            });

        $testApp->method('offsetGet')
            ->with('log.context')
            ->willReturn($contextRepo);

        $testApp->method('environment')
            ->willReturn(false);

        $testApp->method('bound')
            ->willReturn(false);

        // Mock Context facade
        Context::swap($contextRepo);

        // Set up config expectations - app.url + CORS
        $this->config->expects($this->exactly(2))
            ->method('set')
            ->willReturnCallback(function ($key, $value) {
                $this->assertContains($key, ['app.url', 'cors.allowed_origins']);
            });

        $this->config->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($key) {
                return match ($key) {
                    'app.url' => 'https://test.example.com',
                    default => null,
                };
            });

        // Act
        $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });
        
        // Restore the original app instance for other tests
        Container::setInstance($this->app);
    }

    /**
     * Test that the handles method returns the correct keys.
     */
    public function testHandlesReturnsCorrectKeys(): void
    {
        // Act
        $handles = $this->pipe->handles();

        // Assert
        $expectedKeys = [
            'app_name',
            'app_env',
            'app_key',
            'app_debug',
            'app_url',
            'app_timezone',
            'app_locale',
            'app_fallback_locale',
            'frontend_url',
            'internal_api_url',
            'capacitor_scheme',
            'pusher_app_key',
            'pusher_app_secret',
            'pusher_app_id',
            'pusher_app_cluster',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertContains($key, $handles);
        }
    }

    /**
     * Test that the priority method returns the correct value.
     */
    public function testPriorityReturnsCorrectValue(): void
    {
        // Act
        $priority = $this->pipe->priority();

        // Assert
        $this->assertEquals(100, $priority);
    }

    /**
     * Test that the handle method handles empty tenant config gracefully.
     */
    public function testHandleWithEmptyTenantConfig(): void
    {
        // Arrange
        $tenant            = $this->createMock(Tenant::class);
        $tenant->public_id = 'tenant-123';
        $tenantConfig      = [];

        // Config should not be called to set anything
        $this->config->expects($this->never())
            ->method('set');

        // Act
        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        // Assert
        $this->assertSame($tenant, $result['tenant']);
    }

    /**
     * Clean up after each test.
     */
    protected function tearDown(): void
    {
        // Restore the original container instance
        if ($this->originalContainer) {
            Container::setInstance($this->originalContainer);
        }

        parent::tearDown();
    }
}
