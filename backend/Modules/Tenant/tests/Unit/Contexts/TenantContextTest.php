<?php

namespace Modules\Tenant\Tests\Unit\Contexts;

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\TenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantContext::class)]
#[Group('tenant-module')]
#[Group('tenant-contexts')]
class TenantContextTest extends TestCase
{
    /**
     * Test setting and getting the tenant.
     */
    public function test_set_and_get_tenant(): void
    {
        $tenant = Tenant::factory()->make();
        $context = new TenantContext;

        $context->set($tenant);

        $this->assertSame($tenant, $context->get());
    }

    /**
     * Test getting tenant when none is set throws exception.
     */
    public function test_get_tenant_throws_exception_when_not_set(): void
    {
        $this->expectException(TenantNotFoundException::class);

        $context = new TenantContext;
        $context->get();
    }

    /**
     * Test retrieving tenant config.
     */
    public function test_get_config(): void
    {
        $tenant = Tenant::factory()->make([
            'config' => new TenantConfig(
                apiUrl: 'https://api.example.com',
                appUrl: 'https://app.example.com',
                appName: 'Test Tenant',
                appEnv: 'local',
            ),
        ]);

        $context = new TenantContext;
        $context->set($tenant);

        $this->assertInstanceOf(TenantConfig::class, $context->getConfig());
        $this->assertEquals('https://api.example.com', $context->getConfig()->apiUrl);
    }

    /**
     * Test retrieving a specific config value.
     */
    public function test_get_config_value(): void
    {
        $tenant = Tenant::factory()->make([
            'config' => new TenantConfig(
                apiUrl: 'https://api.example.com',
                appUrl: 'https://app.example.com',
                appName: 'Test Tenant',
                appEnv: 'local',
            ),
        ]);

        $context = new TenantContext;
        $context->set($tenant);

        $this->assertEquals(
            'https://app.example.com',
            $context->getConfigValue('appUrl'),
        );

        $this->assertEquals(
            'local',
            $context->getConfigValue('appEnv'),
        );
    }

    /**
     * Test retrieving a missing config value returns the default.
     */
    public function test_get_config_value_returns_default_when_missing(): void
    {
        $tenant = Tenant::factory()->make([
            'config' => new TenantConfig(
                apiUrl: 'https://api.example.com',
                appUrl: 'https://app.example.com',
                appName: 'Test Tenant',
                appEnv: 'local',
            ),
        ]);

        $context = new TenantContext;
        $context->set($tenant);

        $this->assertEquals(
            'Default Value',
            $context->getConfigValue(
                'invalid',
                'Default Value',
            ),
        );
    }
}
