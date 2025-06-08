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

        $this->provider      = new AuthTenantConfigProvider();
        $this->tenantModel   = Mockery::mock(Tenant::class);
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
        $this->tenantModel->shouldReceive('getEffectiveConfig')
            ->andReturn($this->dynamicConfig);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('socialite_providers', ['google'])
            ->andReturn(['github', 'gitlab']);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_cookie', 'quvel_session')
            ->andReturn('custom_session');

        $result = $this->provider->getConfig($this->tenantModel);

        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('visibility', $result);

        $this->assertEquals([
            'socialiteProviders' => ['github', 'gitlab'],
            'sessionCookie'      => 'custom_session',
        ], $result['config']);

        $this->assertEquals([
            'socialiteProviders' => 'public',
            'sessionCookie'      => 'protected',
        ], $result['visibility']);
    }

    #[TestDox('returns default values when tenant config values are not set')]
    public function testReturnsDefaultValuesWhenTenantConfigValuesAreNotSet(): void
    {
        $this->tenantModel->shouldReceive('getEffectiveConfig')
            ->andReturn($this->dynamicConfig);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('socialite_providers', ['google'])
            ->andReturn(['google']);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_cookie', 'quvel_session')
            ->andReturn('quvel_session');

        $result = $this->provider->getConfig($this->tenantModel);

        $this->assertEquals([
            'socialiteProviders' => ['google'],
            'sessionCookie'      => 'quvel_session',
        ], $result['config']);

        $this->assertEquals([
            'socialiteProviders' => 'public',
            'sessionCookie'      => 'protected',
        ], $result['visibility']);
    }

    #[TestDox('returns empty config array when tenant has no dynamic config')]
    public function testReturnsEmptyConfigArrayWhenTenantHasNoDynamicConfig(): void
    {
        $this->tenantModel->shouldReceive('getEffectiveConfig')
            ->andReturn(null);

        $result = $this->provider->getConfig($this->tenantModel);

        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('visibility', $result);
        $this->assertEquals([], $result['config']);

        // Visibility should still be included
        $this->assertEquals([
        ], $result['visibility']);
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
            $this->provider,
        );
    }

    #[TestDox('visibility settings are consistent regardless of config values')]
    public function testVisibilitySettingsAreConsistentRegardlessOfConfigValues(): void
    {
        $this->tenantModel->shouldReceive('getEffectiveConfig')
            ->andReturn(null, $this->dynamicConfig);

        $result1 = $this->provider->getConfig($this->tenantModel);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('socialite_providers', ['google'])
            ->andReturn(['github']);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_cookie', 'quvel_session')
            ->andReturn('quvel_session');

        $result2 = $this->provider->getConfig($this->tenantModel);

        $this->assertEquals($result1['visibility'], $result2['visibility']);
    }

    #[TestDox('handles empty socialite provider array')]
    public function testHandlesEmptySocialiteProviderArray(): void
    {
        $this->tenantModel->shouldReceive('getEffectiveConfig')
            ->andReturn($this->dynamicConfig);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('socialite_providers', ['google'])
            ->andReturn([]);

        $this->dynamicConfig
            ->shouldReceive('get')
            ->with('session_cookie', 'quvel_session')
            ->andReturn('quvel_session');

        $result = $this->provider->getConfig($this->tenantModel);
        $this->assertEquals([], $result['config']['socialiteProviders']);
    }
}
