<?php

namespace Modules\Core\Tests\Unit\Providers;

use Modules\Core\Providers\CoreTenantConfigProvider;
use Modules\Tenant\Contracts\TenantConfigProviderInterface;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

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

    #[Test]
    public function it_implements_tenant_config_provider_interface(): void
    {
        $this->assertInstanceOf(TenantConfigProviderInterface::class, $this->provider);
    }

    #[Test]
    public function it_has_high_priority(): void
    {
        $this->assertEquals(100, $this->provider->priority());
    }

    #[Test]
    public function it_returns_config_with_default_values_when_tenant_has_no_config(): void
    {
        // Set default config values
        config([
            'app.url' => 'https://api.default.com',
            'app.name' => 'Default App',
            'frontend.url' => 'https://default.com',
            'frontend.internal_api_url' => 'http://internal.default.com'
        ]);
        
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'test-tenant-123';
        $tenant->name = 'Test Tenant';
        
        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn(null);
            
        $result = $this->provider->getConfig($tenant);
        
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

    #[Test]
    public function it_returns_config_from_tenant_config_when_available(): void
    {
        $tenantConfig = new DynamicTenantConfig();
        $tenantConfig->set('app_url', 'https://api.tenant.com');
        $tenantConfig->set('frontend_url', 'https://tenant.com');
        $tenantConfig->set('app_name', 'Tenant App');
        $tenantConfig->set('pusher_app_key', 'tenant-pusher-key');
        $tenantConfig->set('pusher_app_cluster', 'us2');
        $tenantConfig->set('recaptcha_site_key', 'tenant-recaptcha-key');
        $tenantConfig->set('internal_api_url', 'http://internal.tenant.com');
        
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'tenant-456';
        $tenant->name = 'Custom Tenant';
        
        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($tenantConfig);
            
        $result = $this->provider->getConfig($tenant);
        
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

    #[Test]
    public function it_falls_back_to_config_when_tenant_config_missing_keys(): void
    {
        // Set default config values
        config([
            'app.url' => 'https://api.fallback.com',
            'app.name' => 'Fallback App',
            'frontend.url' => 'https://fallback.com',
            'frontend.internal_api_url' => 'http://internal.fallback.com'
        ]);
        
        // Tenant config with only some values
        $tenantConfig = new DynamicTenantConfig();
        $tenantConfig->set('app_name', 'Partial Tenant');
        $tenantConfig->set('pusher_app_key', 'partial-key');
        
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'partial-tenant';
        $tenant->name = 'Partial Config Tenant';
        
        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($tenantConfig);
            
        $result = $this->provider->getConfig($tenant);
        
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

    #[Test]
    public function it_uses_default_app_name_when_config_not_set(): void
    {
        // Don't set app.name config
        config(['app.name' => null]);
        
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'test';
        $tenant->name = 'Test';
        
        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn(null);
            
        $result = $this->provider->getConfig($tenant);
        
        // Should use the default 'Quvel Kit'
        $this->assertEquals('Quvel Kit', $result['config']['appName']);
    }

    #[Test]
    public function it_returns_correct_visibility_for_all_config_keys(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'test';
        $tenant->name = 'Test';
        
        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn(null);
            
        $result = $this->provider->getConfig($tenant);
        
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

    #[Test]
    public function it_handles_empty_pusher_config_gracefully(): void
    {
        $tenantConfig = new DynamicTenantConfig();
        // Don't set any pusher config
        
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'test';
        $tenant->name = 'Test';
        
        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($tenantConfig);
            
        $result = $this->provider->getConfig($tenant);
        
        // Should have default values
        $config = $result['config'];
        $this->assertEquals('', $config['pusherAppKey']);
        $this->assertEquals('mt1', $config['pusherAppCluster']);
    }

    #[Test]
    public function it_handles_empty_recaptcha_config_gracefully(): void
    {
        $tenantConfig = new DynamicTenantConfig();
        // Don't set any recaptcha config
        
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'test';
        $tenant->name = 'Test';
        
        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn($tenantConfig);
            
        $result = $this->provider->getConfig($tenant);
        
        // Should have default value
        $config = $result['config'];
        $this->assertEquals('', $config['recaptchaGoogleSiteKey']);
    }

    #[Test]
    public function it_includes_all_expected_config_keys(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'test';
        $tenant->name = 'Test';
        
        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn(null);
            
        $result = $this->provider->getConfig($tenant);
        
        $expectedKeys = [
            'apiUrl',
            'appUrl',
            'appName',
            'tenantId',
            'tenantName',
            'pusherAppKey',
            'pusherAppCluster',
            'recaptchaGoogleSiteKey',
            'internalApiUrl'
        ];
        
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result['config'], "Config should include key: $key");
            $this->assertArrayHasKey($key, $result['visibility'], "Visibility should include key: $key");
        }
    }

    #[Test]
    public function it_returns_consistent_structure(): void
    {
        $tenant = $this->createMock(Tenant::class);
        $tenant->public_id = 'test';
        $tenant->name = 'Test';
        
        $tenant->expects($this->once())
            ->method('getEffectiveConfig')
            ->willReturn(null);
            
        $result = $this->provider->getConfig($tenant);
        
        // Verify top-level structure
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('visibility', $result);
        
        // Verify config and visibility are arrays
        $this->assertIsArray($result['config']);
        $this->assertIsArray($result['visibility']);
        
        // Verify same keys in both
        $configKeys = array_keys($result['config']);
        $visibilityKeys = array_keys($result['visibility']);
        
        sort($configKeys);
        sort($visibilityKeys);
        
        $this->assertEquals($configKeys, $visibilityKeys);
    }
}