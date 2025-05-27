<?php

namespace Modules\Auth\Tests\Unit\Providers;

use Modules\Auth\Providers\AuthTenantConfigProvider;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Mockery;

#[CoversClass(AuthTenantConfigProvider::class)]
#[Group('auth-module')]
#[Group('tenant-config-providers')]
class AuthTenantConfigProviderTest extends TestCase
{
    private AuthTenantConfigProvider $provider;
    private Tenant $tenantModel;
    private DynamicTenantConfig $dynamicConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new AuthTenantConfigProvider();
        $this->tenantModel = Mockery::mock(Tenant::class);
        $this->dynamicConfig = Mockery::mock(DynamicTenantConfig::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[TestDox('returns configuration with all auth settings when tenant has dynamic config')]
    public function testReturnsConfigurationWithAllAuthSettingsWhenTenantHasDynamicConfig(): void
    {
        $this->tenantModel->shouldReceive('getAttribute')
            ->with('config')
            ->andReturn($this->dynamicConfig);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('socialite_providers', ['google'])
            ->andReturn(['github', 'gitlab']);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('password_min_length', 8)
            ->andReturn(12);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_cookie', 'quvel_session')
            ->andReturn('custom_session');
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('two_factor_enabled', false)
            ->andReturn(true);
        
        $this->dynamicConfig
            ->shouldReceive('has')
            ->with('session_lifetime')
            ->andReturn(true);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_lifetime')
            ->andReturn(120);

        $result = $this->provider->getConfig($this->tenantModel);

        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('visibility', $result);

        $this->assertEquals([
            'socialiteProviders' => ['github', 'gitlab'],
            'passwordMinLength' => 12,
            'sessionCookie' => 'custom_session',
            'twoFactorEnabled' => true,
            'sessionLifetime' => 120,
        ], $result['config']);

        $this->assertEquals([
            'socialiteProviders' => 'public',
            'passwordMinLength' => 'public',
            'sessionCookie' => 'protected',
            'twoFactorEnabled' => 'public',
            'sessionLifetime' => 'protected',
        ], $result['visibility']);
    }

    #[TestDox('returns default values when tenant config values are not set')]
    public function testReturnsDefaultValuesWhenTenantConfigValuesAreNotSet(): void
    {
        $this->tenantModel->shouldReceive('getAttribute')
            ->with('config')
            ->andReturn($this->dynamicConfig);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('socialite_providers', ['google'])
            ->andReturn(['google']);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('password_min_length', 8)
            ->andReturn(8);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_cookie', 'quvel_session')
            ->andReturn('quvel_session');
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('two_factor_enabled', false)
            ->andReturn(false);
        
        $this->dynamicConfig
            ->shouldReceive('has')
            ->with('session_lifetime')
            ->andReturn(false);

        $result = $this->provider->getConfig($this->tenantModel);

        $this->assertEquals([
            'socialiteProviders' => ['google'],
            'passwordMinLength' => 8,
            'sessionCookie' => 'quvel_session',
            'twoFactorEnabled' => false,
        ], $result['config']);

        $this->assertEquals([
            'socialiteProviders' => 'public',
            'passwordMinLength' => 'public',
            'sessionCookie' => 'protected',
            'twoFactorEnabled' => 'public',
            'sessionLifetime' => 'protected',
        ], $result['visibility']);
    }

    #[TestDox('returns empty config array when tenant has no dynamic config')]
    public function testReturnsEmptyConfigArrayWhenTenantHasNoDynamicConfig(): void
    {
        $this->tenantModel->shouldReceive('getAttribute')
            ->with('config')
            ->andReturn(null);

        $result = $this->provider->getConfig($this->tenantModel);

        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('visibility', $result);
        $this->assertEquals([], $result['config']);

        // Visibility should still be included
        $this->assertEquals([
            'socialiteProviders' => 'public',
            'passwordMinLength' => 'public',
            'sessionCookie' => 'protected',
            'twoFactorEnabled' => 'public',
            'sessionLifetime' => 'protected',
        ], $result['visibility']);
    }

    #[TestDox('includes session lifetime only when present in tenant config')]
    public function testIncludesSessionLifetimeOnlyWhenPresentInTenantConfig(): void
    {
        $this->tenantModel->shouldReceive('getAttribute')
            ->with('config')
            ->andReturn($this->dynamicConfig);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('socialite_providers', ['google'])
            ->andReturn(['google']);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('password_min_length', 8)
            ->andReturn(8);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_cookie', 'quvel_session')
            ->andReturn('quvel_session');
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('two_factor_enabled', false)
            ->andReturn(false);
        
        $this->dynamicConfig
            ->shouldReceive('has')
            ->with('session_lifetime')
            ->andReturn(true);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_lifetime')
            ->andReturn(60);

        $result = $this->provider->getConfig($this->tenantModel);

        $this->assertArrayHasKey('sessionLifetime', $result['config']);
        $this->assertEquals(60, $result['config']['sessionLifetime']);
    }

    #[TestDox('returns priority of 50')]
    public function testReturnsPriorityOf50(): void
    {
        $this->assertEquals(50, $this->provider->priority());
    }

    #[TestDox('implements tenant config provider interface')]
    public function testImplementsTenantConfigProviderInterface(): void
    {
        $this->assertInstanceOf(
            \Modules\Tenant\Contracts\TenantConfigProviderInterface::class,
            $this->provider
        );
    }

    #[TestDox('visibility settings are consistent regardless of config values')]
    public function testVisibilitySettingsAreConsistentRegardlessOfConfigValues(): void
    {
        $this->tenantModel->shouldReceive('getAttribute')
            ->with('config')
            ->andReturn(null, $this->dynamicConfig);
            
        $result1 = $this->provider->getConfig($this->tenantModel);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('socialite_providers', ['google'])
            ->andReturn(['github']);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('password_min_length', 8)
            ->andReturn(8);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_cookie', 'quvel_session')
            ->andReturn('quvel_session');
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('two_factor_enabled', false)
            ->andReturn(false);
        
        $this->dynamicConfig
            ->shouldReceive('has')
            ->with('session_lifetime')
            ->andReturn(false);
        
        $result2 = $this->provider->getConfig($this->tenantModel);

        $this->assertEquals($result1['visibility'], $result2['visibility']);
    }

    #[TestDox('handles empty socialite provider array')]
    public function testHandlesEmptySocialiteProviderArray(): void
    {
        $this->tenantModel->shouldReceive('getAttribute')
            ->with('config')
            ->andReturn($this->dynamicConfig);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('socialite_providers', ['google'])
            ->andReturn([]);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('password_min_length', 8)
            ->andReturn(8);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_cookie', 'quvel_session')
            ->andReturn('quvel_session');
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('two_factor_enabled', false)
            ->andReturn(false);
        
        $this->dynamicConfig
            ->shouldReceive('has')
            ->with('session_lifetime')
            ->andReturn(false);

        $result = $this->provider->getConfig($this->tenantModel);
        $this->assertEquals([], $result['config']['socialiteProviders']);
    }

    #[TestDox('handles various password min length values')]
    public function testHandlesVariousPasswordMinLengthValues(): void
    {
        $this->tenantModel->shouldReceive('getAttribute')
            ->with('config')
            ->andReturn($this->dynamicConfig);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('socialite_providers', ['google'])
            ->andReturn(['google']);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('password_min_length', 8)
            ->andReturn(16);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_cookie', 'quvel_session')
            ->andReturn('quvel_session');
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('two_factor_enabled', false)
            ->andReturn(false);
        
        $this->dynamicConfig
            ->shouldReceive('has')
            ->with('session_lifetime')
            ->andReturn(false);

        $result = $this->provider->getConfig($this->tenantModel);
        $this->assertEquals(16, $result['config']['passwordMinLength']);
    }

    #[TestDox('handles boolean two factor enabled configuration')]
    public function testHandlesBooleanTwoFactorEnabledConfiguration(): void
    {
        $this->tenantModel->shouldReceive('getAttribute')
            ->with('config')
            ->andReturn($this->dynamicConfig);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('socialite_providers', ['google'])
            ->andReturn(['google']);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('password_min_length', 8)
            ->andReturn(8);
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_cookie', 'quvel_session')
            ->andReturn('quvel_session');
        
        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('two_factor_enabled', false)
            ->andReturn(true);
        
        $this->dynamicConfig
            ->shouldReceive('has')
            ->with('session_lifetime')
            ->andReturn(false);

        $result = $this->provider->getConfig($this->tenantModel);
        $this->assertTrue($result['config']['twoFactorEnabled']);
    }
}