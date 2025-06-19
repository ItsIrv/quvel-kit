<?php

namespace Modules\Tenant\Tests\Unit\Pipes;

use Illuminate\Container\Container;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Application;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Context;
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
    private CoreConfigPipe $pipe;
    private ConfigRepository|MockObject $config;
    private Application|MockObject $app;
    private ?Container $originalContainer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalContainer = Container::getInstance();

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

        Container::setInstance($this->app);

        $contextRepo = $this->createMock(\Illuminate\Log\Context\Repository::class);
        $contextRepo->expects($this->any())
            ->method('add')
            ->willReturn(null);

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

        Context::swap($contextRepo);

        $this->pipe = new CoreConfigPipe();
    }

    public function testHandleSetsAppConfiguration(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'tenant-123';

        $tenantConfig = [
            'app_name' => 'Test App',
            'app_env' => 'testing',
            'app_key' => 'base64:test-key',
            'app_debug' => true,
            'app_url' => 'https://test.example.com',
            'app_timezone' => 'America/New_York',
            'app_locale' => 'en',
            'app_fallback_locale' => 'en',
        ];

        $this->config->expects($this->exactly(9))
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

        $result = $this->pipe->handle($tenant, $this->config, $tenantConfig, function ($data) {
            return $data;
        });

        $this->assertSame($tenant, $result['tenant']);
        $this->assertSame($this->config, $result['config']);
        $this->assertSame($tenantConfig, $result['tenantConfig']);
    }

    public function testResolveReturnsCorrectValuesAndVisibility(): void
    {
        $tenant = new Tenant(['name' => 'Test Tenant']);
        $tenant->public_id = 'tenant-123';

        $tenantConfig = [
            'app_url' => 'https://api.example.com',
            'frontend_url' => 'https://app.example.com',
            'app_name' => 'Test App',
            'pusher_app_key' => 'test-key',
            'pusher_app_cluster' => 'us-east-1',
        ];

        $result = $this->pipe->resolve($tenant, $tenantConfig);

        $this->assertArrayHasKey('values', $result);
        $this->assertArrayHasKey('visibility', $result);

        $expectedValues = [
            'apiUrl' => 'https://api.example.com',
            'appUrl' => 'https://app.example.com',
            'appName' => 'Test App',
            'pusherAppKey' => 'test-key',
            'pusherAppCluster' => 'us-east-1',
        ];

        $expectedVisibility = [
            'apiUrl' => 'public',
            'appUrl' => 'public',
            'appName' => 'public',
            'pusherAppKey' => 'public',
            'pusherAppCluster' => 'public',
        ];

        $this->assertEquals($expectedValues, $result['values']);
        $this->assertEquals($expectedVisibility, $result['visibility']);
    }

    public function testResolveFallsBackToAppUrlForFrontendUrl(): void
    {
        $tenant = new Tenant(['name' => 'Test Tenant']);
        $tenant->public_id = 'tenant-123';

        $tenantConfig = [
            'app_url' => 'https://api.example.com',
            'app_name' => 'Test App',
        ];

        $result = $this->pipe->resolve($tenant, $tenantConfig);

        $this->assertEquals([
            'apiUrl' => 'https://api.example.com',
            'appUrl' => 'https://api.example.com', // Falls back to app_url
            'appName' => 'Test App',
        ], $result['values']);

        $this->assertEquals([
            'apiUrl' => 'public',
            'appUrl' => 'public',
            'appName' => 'public',
        ], $result['visibility']);
    }

    public function testResolveWithEmptyConfig(): void
    {
        $tenant = new Tenant(['name' => 'Test Tenant']);
        $tenant->public_id = 'tenant-123';

        $tenantConfig = [];

        $result = $this->pipe->resolve($tenant, $tenantConfig);

        $this->assertEquals([
            'values' => [],
            'visibility' => []
        ], $result);
    }

    public function testResolveWithPartialConfig(): void
    {
        $tenant = new Tenant(['name' => 'Test Tenant']);
        $tenant->public_id = 'tenant-123';

        $tenantConfig = [
            'app_name' => 'Test App',
            'pusher_app_key' => 'test-key',
            // Missing other fields
        ];

        $result = $this->pipe->resolve($tenant, $tenantConfig);

        $this->assertEquals([
            'appName' => 'Test App',
            'pusherAppKey' => 'test-key',
        ], $result['values']);

        $this->assertEquals([
            'appName' => 'public',
            'pusherAppKey' => 'public',
        ], $result['visibility']);
    }


    protected function tearDown(): void
    {
        if ($this->originalContainer) {
            Container::setInstance($this->originalContainer);
        }

        parent::tearDown();
    }
}
