<?php

namespace Modules\Auth\Tests\Unit\Providers;

use Modules\Auth\Providers\AuthTenantConfigProvider;
use Modules\Tenant\Models\Tenant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Mockery;
use Illuminate\Config\Repository;

#[CoversClass(AuthTenantConfigProvider::class)]
#[Group('auth-module')]
#[Group('auth-providers')]
#[Group('tenant-config-providers')]
class AuthTenantConfigProviderTest extends TestCase
{
    private AuthTenantConfigProvider $provider;
    private Tenant $tenantModel;
    private Repository $configRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider         = new AuthTenantConfigProvider();
        $this->tenantModel      = Mockery::mock(Tenant::class);
        $this->configRepository = Mockery::mock(Repository::class);

        // Bind the mock to the container
        $this->app->instance(Repository::class, $this->configRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[TestDox('returns configuration with all auth settings from Laravel config')]
    public function testReturnsConfigurationWithAllAuthSettingsFromLaravelConfig(): void
    {
        // Mock config values that would be set by configuration pipes
        $this->configRepository
            ->shouldReceive('get')
            ->with('auth.socialite.providers', ['google'])
            ->andReturn(['github', 'gitlab']);

        $this->configRepository
            ->shouldReceive('get')
            ->with('session.cookie', 'quvel_session')
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

    #[TestDox('returns default values when config values are not set')]
    public function testReturnsDefaultValuesWhenConfigValuesAreNotSet(): void
    {
        // Mock config values returning defaults
        $this->configRepository
            ->shouldReceive('get')
            ->with('auth.socialite.providers', ['google'])
            ->andReturn(['google']);

        $this->configRepository
            ->shouldReceive('get')
            ->with('session.cookie', 'quvel_session')
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

    #[TestDox('returns config from Laravel config repository')]
    public function testReturnsConfigFromLaravelConfigRepository(): void
    {
        // Mock config returning specific values
        $this->configRepository
            ->shouldReceive('get')
            ->with('auth.socialite.providers', ['google'])
            ->andReturn(['facebook', 'twitter']);

        $this->configRepository
            ->shouldReceive('get')
            ->with('session.cookie', 'quvel_session')
            ->andReturn('tenant_session');

        $result = $this->provider->getConfig($this->tenantModel);

        $this->assertArrayHasKey('config', $result);
        $this->assertArrayHasKey('visibility', $result);

        $this->assertEquals([
            'socialiteProviders' => ['facebook', 'twitter'],
            'sessionCookie'      => 'tenant_session',
        ], $result['config']);

        // Visibility should still be included
        $this->assertEquals([
            'socialiteProviders' => 'public',
            'sessionCookie'      => 'protected',
        ], $result['visibility']);
    }

    #[TestDox('handles empty socialite provider array')]
    public function testHandlesEmptySocialiteProviderArray(): void
    {
        $this->configRepository
            ->shouldReceive('get')
            ->with('auth.socialite.providers', ['google'])
            ->andReturn([]);

        $this->configRepository
            ->shouldReceive('get')
            ->with('session.cookie', 'quvel_session')
            ->andReturn('quvel_session');

        $result = $this->provider->getConfig($this->tenantModel);
        $this->assertEquals([], $result['config']['socialiteProviders']);
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
        // First call
        $this->configRepository
            ->shouldReceive('get')
            ->with('auth.socialite.providers', ['google'])
            ->andReturn(['google']);

        $this->configRepository
            ->shouldReceive('get')
            ->with('session.cookie', 'quvel_session')
            ->andReturn('quvel_session');

        $result1 = $this->provider->getConfig($this->tenantModel);

        // Create a new provider instance to test consistency
        $provider2 = new AuthTenantConfigProvider();

        // Second call with different values
        $this->configRepository
            ->shouldReceive('get')
            ->with('auth.socialite.providers', ['google'])
            ->andReturn(['github']);

        $this->configRepository
            ->shouldReceive('get')
            ->with('session.cookie', 'quvel_session')
            ->andReturn('custom_session');

        $result2 = $provider2->getConfig($this->tenantModel);

        $this->assertEquals($result1['visibility'], $result2['visibility']);
    }

}
