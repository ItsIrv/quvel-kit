<?php

namespace Modules\Tenant\Tests\Unit\Contexts;

use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\database\factories\DynamicTenantConfigFactory;
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
    public function testSetAndGetTenant(): void
    {
        $tenant  = Tenant::factory()->make();
        $context = new TenantContext();

        $context->set($tenant);

        $this->assertSame($tenant, $context->get());
    }

    /**
     * Test getting tenant when none is set throws exception.
     */
    public function testGetTenantThrowsExceptionWhenNotSet(): void
    {
        $this->expectException(TenantNotFoundException::class);

        $context = new TenantContext();
        $context->get();
    }

    /**
     * Test retrieving tenant config.
     */
    public function testGetConfig(): void
    {
        $config = DynamicTenantConfigFactory::createStandardTier(
            domain: 'api.example.com',
            appName: 'Example App'
        );
        
        $tenant = Tenant::factory()->make([
            'config' => $config,
        ]);

        $context = new TenantContext();
        $context->set($tenant);

        $this->assertInstanceOf(DynamicTenantConfig::class, $context->getConfig());
        $this->assertEquals('api.example.com', $context->getConfig()->get('domain'));
    }

    /**
     * Test retrieving a specific config value.
     */
    public function testGetConfigValue(): void
    {
        $config = DynamicTenantConfigFactory::createStandardTier(
            domain: 'api.example.com',
            appName: 'Example App'
        );
        
        $tenant = Tenant::factory()->make([
            'config' => $config,
        ]);

        $context = new TenantContext();
        $context->set($tenant);

        $this->assertEquals(
            'api.example.com',
            $context->getConfigValue('domain'),
        );

        $this->assertEquals(
            'standard',
            $context->getConfigValue('tier'),
        );
    }

    /**
     * Test retrieving a missing config value returns the default.
     */
    public function testGetConfigValueReturnsDefaultWhenMissing(): void
    {
        $config = DynamicTenantConfigFactory::createStandardTier(
            domain: 'api.example.com',
            appName: 'Example App'
        );
        
        $tenant = Tenant::factory()->make([
            'config' => $config,
        ]);

        $context = new TenantContext();
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
