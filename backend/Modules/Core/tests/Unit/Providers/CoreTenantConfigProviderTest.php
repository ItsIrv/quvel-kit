<?php

namespace Modules\Core\Tests\Unit\Providers;

use Modules\Core\Providers\CoreTenantConfigProvider;
use Modules\Tenant\Contracts\TenantConfigProviderInterface;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

/**
 * @testdox CoreTenantConfigProvider
 */
#[CoversClass(CoreTenantConfigProvider::class)]
#[Group('core-module')]
#[Group('core-providers')]
class CoreTenantConfigProviderTest extends TestCase
{
    private CoreTenantConfigProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new CoreTenantConfigProvider();
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

    #[TestDox('returns config with default values when tenant has no config')]
    public function testReturnsConfigWithDefaultValuesWhenTenantHasNoConfig(): void
    {
        // Set default config values
        config([
            'app.url'                   => 'https://api.default.com',
            'app.name'                  => 'Default App',
            'frontend.url'              => 'https://default.com',
            'frontend.internal_api_url' => 'http://internal.default.com',
        ]);

        $tenantModel            = $this->createPartialMock(Tenant::class, ['getEffectiveConfig']);
        $tenantModel->public_id = 'test-tenant-123';
        $tenantModel->name      = 'Test Tenant';

        $tenantModel->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn(null);

        $result = $this->provider->getConfig($tenantModel);

        // Check config structure
        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('visibility', $result);

        // Check default values are used
        $config = $result['config'];
        $this->assertEquals('https://api.default.com', $config['apiUrl']);
        $this->assertEquals('https://default.com', $config['appUrl']);
        $this->assertEquals('Default App', $config['appName']);
        $this->assertEquals('test-tenant-123', $config['tenantId']);
        $this->assertEquals('Test Tenant', $config['tenantName']);
        $this->assertEquals('', $config['pusherAppKey']);
        $this->assertEquals('mt1', $config['pusherAppCluster']);
        $this->assertEquals('', $config['recaptchaGoogleSiteKey']);
        $this->assertEquals('http://internal.default.com', $config['internalApiUrl']);
    }

    #[TestDox('returns config from tenant config when available')]
    public function testReturnsConfigFromTenantConfigWhenAvailable(): void
    {
        $tenantConfig = new DynamicTenantConfig();
        $tenantConfig->set('app_url', 'https://api.tenant.com');
        $tenantConfig->set('frontend_url', 'https://tenant.com');
        $tenantConfig->set('app_name', 'Tenant App');
        $tenantConfig->set('pusher_app_key', 'tenant-pusher-key');
        $tenantConfig->set('pusher_app_cluster', 'us2');
        $tenantConfig->set('recaptcha_site_key', 'tenant-recaptcha-key');
        $tenantConfig->set('internal_api_url', 'http://internal.tenant.com');

        $tenantModel            = $this->createPartialMock(Tenant::class, ['getEffectiveConfig']);
        $tenantModel->public_id = 'tenant-456';
        $tenantModel->name      = 'Custom Tenant';

        $tenantModel->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($tenantConfig);

        $result = $this->provider->getConfig($tenantModel);

        // Check tenant config values are used
        $config = $result['config'];
        $this->assertEquals('https://api.tenant.com', $config['apiUrl']);
        $this->assertEquals('https://tenant.com', $config['appUrl']);
        $this->assertEquals('Tenant App', $config['appName']);
        $this->assertEquals('tenant-456', $config['tenantId']);
        $this->assertEquals('Custom Tenant', $config['tenantName']);
        $this->assertEquals('tenant-pusher-key', $config['pusherAppKey']);
        $this->assertEquals('us2', $config['pusherAppCluster']);
        $this->assertEquals('tenant-recaptcha-key', $config['recaptchaGoogleSiteKey']);
        $this->assertEquals('http://internal.tenant.com', $config['internalApiUrl']);
    }

    #[TestDox('falls back to config when tenant config missing keys')]
    public function testFallsBackToConfigWhenTenantConfigMissingKeys(): void
    {
        // Set default config values
        config([
            'app.url'                   => 'https://api.fallback.com',
            'app.name'                  => 'Fallback App',
            'frontend.url'              => 'https://fallback.com',
            'frontend.internal_api_url' => 'http://internal.fallback.com',
        ]);

        // Tenant config with only some values
        $tenantConfig = new DynamicTenantConfig();
        $tenantConfig->set('app_name', 'Partial Tenant');
        $tenantConfig->set('pusher_app_key', 'partial-key');

        $tenantModel            = $this->createPartialMock(Tenant::class, ['getEffectiveConfig']);
        $tenantModel->public_id = 'partial-tenant';
        $tenantModel->name      = 'Partial Config Tenant';

        $tenantModel->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($tenantConfig);

        $result = $this->provider->getConfig($tenantModel);

        // Check mixed values
        $config = $result['config'];
        $this->assertEquals('https://api.fallback.com', $config['apiUrl']); // From config
        $this->assertEquals('https://fallback.com', $config['appUrl']); // From config
        $this->assertEquals('Partial Tenant', $config['appName']); // From tenant
        $this->assertEquals('partial-key', $config['pusherAppKey']); // From tenant
        $this->assertEquals('mt1', $config['pusherAppCluster']); // Default
        $this->assertEquals('', $config['recaptchaGoogleSiteKey']); // Default
        $this->assertEquals('http://internal.fallback.com', $config['internalApiUrl']); // From config
    }

    #[TestDox('uses default app name when config not set')]
    public function testUsesDefaultAppNameWhenConfigNotSet(): void
    {
        // Don't set any app.name config, so it defaults to the fallback
        // Clear any existing config first
        $this->app['config']->offsetUnset('app.name');

        $tenantModel            = $this->createPartialMock(Tenant::class, ['getEffectiveConfig']);
        $tenantModel->public_id = 'test';
        $tenantModel->name      = 'Test';

        $tenantModel->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn(null);

        $result = $this->provider->getConfig($tenantModel);

        // Should use the default 'Quvel Kit' when config is not set
        $this->assertEquals(config('app.name'), $result['config']['appName']);
    }

    #[TestDox('returns correct visibility for all config keys')]
    public function testReturnsCorrectVisibilityForAllConfigKeys(): void
    {
        $tenantModel            = $this->createPartialMock(Tenant::class, ['getEffectiveConfig']);
        $tenantModel->public_id = 'test';
        $tenantModel->name      = 'Test';

        $tenantModel->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn(null);

        $result = $this->provider->getConfig($tenantModel);

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

    #[TestDox('handles empty pusher config gracefully')]
    public function testHandlesEmptyPusherConfigGracefully(): void
    {
        $tenantConfig = new DynamicTenantConfig();
        // Don't set any pusher config

        $tenantModel            = $this->createPartialMock(Tenant::class, ['getEffectiveConfig']);
        $tenantModel->public_id = 'test';
        $tenantModel->name      = 'Test';

        $tenantModel->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($tenantConfig);

        $result = $this->provider->getConfig($tenantModel);

        // Should have default values
        $config = $result['config'];
        $this->assertEquals('', $config['pusherAppKey']);
        $this->assertEquals('mt1', $config['pusherAppCluster']);
    }

    #[TestDox('handles empty recaptcha config gracefully')]
    public function testHandlesEmptyRecaptchaConfigGracefully(): void
    {
        $tenantConfig = new DynamicTenantConfig();
        // Don't set any recaptcha config

        $tenantModel            = $this->createPartialMock(Tenant::class, ['getEffectiveConfig']);
        $tenantModel->public_id = 'test';
        $tenantModel->name      = 'Test';

        $tenantModel->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($tenantConfig);

        $result = $this->provider->getConfig($tenantModel);

        // Should have default value
        $config = $result['config'];
        $this->assertEquals('', $config['recaptchaGoogleSiteKey']);
    }

    #[TestDox('includes all expected config keys')]
    public function testIncludesAllExpectedConfigKeys(): void
    {
        $tenantModel            = $this->createPartialMock(Tenant::class, ['getEffectiveConfig']);
        $tenantModel->public_id = 'test';
        $tenantModel->name      = 'Test';

        $tenantModel->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn(null);

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
        $tenantModel            = $this->createPartialMock(Tenant::class, ['getEffectiveConfig']);
        $tenantModel->public_id = 'test';
        $tenantModel->name      = 'Test';

        $tenantModel->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn(null);

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
}
