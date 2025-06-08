<?php

namespace Modules\Core\Tests\Unit\Providers;

use Modules\Core\Providers\CoreTenantConfigProvider;
use Modules\Tenant\Contracts\TenantConfigProviderInterface;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Mockery;
use Illuminate\Config\Repository;

/**
 * @testdox CoreTenantConfigProvider
 */
#[CoversClass(CoreTenantConfigProvider::class)]
#[Group('core-module')]
#[Group('core-providers')]
#[Group('tenant-config-providers')]
class CoreTenantConfigProviderTest extends TestCase
{
    private CoreTenantConfigProvider $provider;
    private Repository $configRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider         = new CoreTenantConfigProvider();
        $this->configRepository = Mockery::mock(Repository::class);

        // Bind the mock to the container
        $this->app->instance(Repository::class, $this->configRepository);
    }

    private function createTenantMock(string $publicId = 'test-tenant-123', string $name = 'Test Tenant', $config = null)
    {
        // Create a simple object to avoid Eloquent complexity
        $tenant            = new \stdClass();
        $tenant->public_id = $publicId;
        $tenant->name      = $name;
        $tenant->config    = $config;

        return $tenant;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[TestDox('implements TenantConfigProviderInterface')]
    public function testImplementsTenantConfigProviderInterface(): void
    {
        $this->assertInstanceOf(TenantConfigProviderInterface::class, $this->provider);
    }

    #[TestDox('has high priority of 100')]
    public function testHasHighPriority(): void
    {
        $this->assertEquals(100, $this->provider->priority());
    }

    #[TestDox('returns config from Laravel config repository')]
    public function testReturnsConfigFromLaravelConfigRepository(): void
    {
        $tenantModel = $this->createTenantMock();

        // Mock config values that would be set by CoreConfigPipe
        $this->configRepository
            ->shouldReceive('get')
            ->with('app.url')
            ->andReturn('https://api.tenant.com');

        $this->configRepository
            ->shouldReceive('get')
            ->with('frontend.url')
            ->andReturn('https://tenant.com');

        $this->configRepository
            ->shouldReceive('get')
            ->with('app.name', 'Quvel Kit')
            ->andReturn('Tenant App');

        $this->configRepository
            ->shouldReceive('get')
            ->with('broadcasting.connections.pusher.key', '')
            ->andReturn('tenant-pusher-key');

        $this->configRepository
            ->shouldReceive('get')
            ->with('broadcasting.connections.pusher.options.cluster', 'mt1')
            ->andReturn('us2');

        $this->configRepository
            ->shouldReceive('get')
            ->with('recaptcha_site_key', '')
            ->andReturn('tenant-recaptcha-key');

        $this->configRepository
            ->shouldReceive('get')
            ->with('frontend.internal_api_url')
            ->andReturn('http://internal.tenant.com');

        $result = $this->provider->getConfig($tenantModel);

        // Check config structure
        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('visibility', $result);

        // Check config values from Laravel config
        $config = $result['config'];
        $this->assertEquals('https://api.tenant.com', $config['apiUrl']);
        $this->assertEquals('https://tenant.com', $config['appUrl']);
        $this->assertEquals('Tenant App', $config['appName']);
        $this->assertEquals('test-tenant-123', $config['tenantId']);
        $this->assertEquals('Test Tenant', $config['tenantName']);
        $this->assertEquals('tenant-pusher-key', $config['pusherAppKey']);
        $this->assertEquals('us2', $config['pusherAppCluster']);
        $this->assertEquals('tenant-recaptcha-key', $config['recaptchaGoogleSiteKey']);
        $this->assertEquals('http://internal.tenant.com', $config['internalApiUrl']);
    }

    #[TestDox('returns default values when config not set')]
    public function testReturnsDefaultValuesWhenConfigNotSet(): void
    {
        $tenantModel = $this->createTenantMock();

        // Mock config returning default values
        $this->configRepository
            ->shouldReceive('get')
            ->with('app.url')
            ->andReturn('https://api.default.com');

        $this->configRepository
            ->shouldReceive('get')
            ->with('frontend.url')
            ->andReturn('https://default.com');

        $this->configRepository
            ->shouldReceive('get')
            ->with('app.name', 'Quvel Kit')
            ->andReturn('Quvel Kit');

        $this->configRepository
            ->shouldReceive('get')
            ->with('broadcasting.connections.pusher.key', '')
            ->andReturn('');

        $this->configRepository
            ->shouldReceive('get')
            ->with('broadcasting.connections.pusher.options.cluster', 'mt1')
            ->andReturn('mt1');

        $this->configRepository
            ->shouldReceive('get')
            ->with('recaptcha_site_key', '')
            ->andReturn('');

        $this->configRepository
            ->shouldReceive('get')
            ->with('frontend.internal_api_url')
            ->andReturn('http://internal.default.com');

        $result = $this->provider->getConfig($tenantModel);

        // Check default values are used
        $config = $result['config'];
        $this->assertEquals('https://api.default.com', $config['apiUrl']);
        $this->assertEquals('https://default.com', $config['appUrl']);
        $this->assertEquals('Quvel Kit', $config['appName']);
        $this->assertEquals('test-tenant-123', $config['tenantId']);
        $this->assertEquals('Test Tenant', $config['tenantName']);
        $this->assertEquals('', $config['pusherAppKey']);
        $this->assertEquals('mt1', $config['pusherAppCluster']);
        $this->assertEquals('', $config['recaptchaGoogleSiteKey']);
        $this->assertEquals('http://internal.default.com', $config['internalApiUrl']);
    }

    #[TestDox('returns correct visibility for all config keys')]
    public function testReturnsCorrectVisibilityForAllConfigKeys(): void
    {
        $tenantModel = $this->createTenantMock();

        // Setup minimal mocks
        $this->setupMinimalConfigMocks();

        $result     = $this->provider->getConfig($tenantModel);
        $visibility = $result['visibility'];

        // Check all public keys
        $this->assertEquals('public', $visibility['apiUrl']);
        $this->assertEquals('public', $visibility['appUrl']);
        $this->assertEquals('public', $visibility['appName']);
        $this->assertEquals('public', $visibility['tenantId']);
        $this->assertEquals('public', $visibility['tenantName']);
        $this->assertEquals('public', $visibility['pusherAppKey']);
        $this->assertEquals('public', $visibility['pusherAppCluster']);
        $this->assertEquals('public', $visibility['recaptchaGoogleSiteKey']);

        // Check protected keys
        $this->assertEquals('protected', $visibility['internalApiUrl']);
    }

    #[TestDox('includes all expected config keys')]
    public function testIncludesAllExpectedConfigKeys(): void
    {
        $tenantModel = $this->createTenantMock();

        // Setup minimal mocks
        $this->setupMinimalConfigMocks();

        $result = $this->provider->getConfig($tenantModel);

        $expectedKeys = [
            'apiUrl',
            'appUrl',
            'appName',
            'tenantId',
            'tenantName',
            'pusherAppKey',
            'pusherAppCluster',
            'recaptchaGoogleSiteKey',
            'internalApiUrl',
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result['config'], "Config should include key: $key");
            $this->assertArrayHasKey($key, $result['visibility'], "Visibility should include key: $key");
        }
    }

    #[TestDox('returns consistent structure')]
    public function testReturnsConsistentStructure(): void
    {
        $tenantModel = $this->createTenantMock();

        // Setup minimal mocks
        $this->setupMinimalConfigMocks();

        $result = $this->provider->getConfig($tenantModel);

        // Verify top-level structure
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('visibility', $result);

        // Verify config and visibility are arrays
        $this->assertIsArray($result['config']);
        $this->assertIsArray($result['visibility']);

        // Verify same keys in both
        $configKeys     = array_keys($result['config']);
        $visibilityKeys = array_keys($result['visibility']);

        sort($configKeys);
        sort($visibilityKeys);

        $this->assertEquals($configKeys, $visibilityKeys);
    }

    #[TestDox('handles recaptcha from tenant config when not in Laravel config')]
    public function testHandlesRecaptchaFromTenantConfigWhenNotInLaravelConfig(): void
    {
        // Create a proper DynamicTenantConfig mock
        $tenantConfig = Mockery::mock(DynamicTenantConfig::class);
        $tenantConfig->shouldReceive('get')
            ->with('recaptcha_site_key', '')
            ->andReturn('tenant-recaptcha-key');

        $tenantModel = $this->createTenantMock('test-tenant-123', 'Test Tenant', $tenantConfig);

        // Setup minimal mocks
        $this->setupMinimalConfigMocks();

        $result = $this->provider->getConfig($tenantModel);

        // Should get recaptcha from tenant config since it's not in a pipe yet
        $this->assertEquals('tenant-recaptcha-key', $result['config']['recaptchaGoogleSiteKey']);
    }

    private function setupMinimalConfigMocks(): void
    {
        $this->configRepository
            ->shouldReceive('get')
            ->with('app.url')
            ->andReturn('https://test.com');

        $this->configRepository
            ->shouldReceive('get')
            ->with('frontend.url')
            ->andReturn('https://frontend.test.com');

        $this->configRepository
            ->shouldReceive('get')
            ->with('app.name', 'Quvel Kit')
            ->andReturn('Test App');

        $this->configRepository
            ->shouldReceive('get')
            ->with('broadcasting.connections.pusher.key', '')
            ->andReturn('test-key');

        $this->configRepository
            ->shouldReceive('get')
            ->with('broadcasting.connections.pusher.options.cluster', 'mt1')
            ->andReturn('mt1');

        $this->configRepository
            ->shouldReceive('get')
            ->with('recaptcha_site_key', '')
            ->andReturn('');

        $this->configRepository
            ->shouldReceive('get')
            ->with('frontend.internal_api_url')
            ->andReturn('http://internal.test.com');

        // Mock tenant config as null by default
        // This is handled by the createTenantMock method
    }
}
