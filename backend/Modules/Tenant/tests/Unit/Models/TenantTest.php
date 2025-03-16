<?php

namespace Modules\Tenant\Tests\Unit\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Database\Factories\TenantFactory;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\TenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(Tenant::class)]
#[Group('tenant-module')]
#[Group('tenant-models')]
class TenantTest extends TestCase
{
    /**
     * Test that the Tenant model has the expected fillable properties.
     */
    public function test_has_fillable_properties(): void
    {
        $tenant = new Tenant;

        $this->assertEquals(
            ['name', 'domain', 'parent_id', 'config'],
            $tenant->getFillable(),
        );
    }

    /**
     * Test that the Tenant model can create a new factory instance.
     */
    public function test_can_create_new_factory_instance(): void
    {
        $factory = Tenant::newFactory();

        $this->assertInstanceOf(
            TenantFactory::class,
            $factory,
        );
    }

    /**
     * Test that the parent relationship returns a BelongsTo instance.
     */
    public function test_parent_relationship(): void
    {
        $tenant = new Tenant;

        $this->assertInstanceOf(BelongsTo::class, $tenant->parent());
    }

    /**
     * Test that the children relationship returns a HasMany instance.
     */
    public function test_children_relationship(): void
    {
        $tenant = new Tenant;

        $this->assertInstanceOf(HasMany::class, $tenant->children());
    }

    /**
     * Test that a tenant can be created.
     */
    public function test_creating_tenant(): void
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Example Tenant',
            'domain' => 'example.com',
        ]);

        $this->assertDatabaseHas('tenants', [
            'id' => $tenant->id,
            'name' => 'Example Tenant',
            'domain' => 'example.com',
        ]);

        $this->assertInstanceOf(Tenant::class, $tenant);
    }

    /**
     * Test that `getEffectiveConfig()` correctly falls back to the parent's config.
     */
    public function test_get_effective_config_inherits_parent_config(): void
    {
        // Create a parent tenant with a config
        $parentTenant = Tenant::factory()->create([
            'config' => new TenantConfig(
                apiUrl: 'https://api.example.com',
                appUrl: 'https://app.example.com',
                appName: 'Parent Tenant',
                appEnv: 'local',
            ),
        ]);

        // Create a child tenant that does not have its own config
        $childTenant = Tenant::factory()->create([
            'parent_id' => $parentTenant->id,
            'config' => null, // No direct config
        ]);

        // Ensure the child's effective config is inherited from the parent
        $this->assertInstanceOf(
            TenantConfig::class,
            $childTenant->getEffectiveConfig(),
        );

        $this->assertEquals(
            'https://api.example.com',
            $childTenant->getEffectiveConfig()->apiUrl,
        );

        $this->assertEquals(
            'Parent Tenant',
            $childTenant->getEffectiveConfig()->appName,
        );
    }

    /**
     * Test that `getEffectiveConfig()` returns its own config when no parent exists.
     */
    public function test_get_effective_config_returns_own_config(): void
    {
        $tenant = Tenant::factory()->create([
            'config' => new TenantConfig(
                apiUrl: 'https://api.own.example.com',
                appUrl: 'https://app.own.example.com',
                appName: 'Standalone Tenant',
                appEnv: 'local',
            ),
        ]);

        $this->assertInstanceOf(
            TenantConfig::class,
            $tenant->getEffectiveConfig(),
        );

        $this->assertEquals(
            'Standalone Tenant',
            $tenant->getEffectiveConfig()->appName,
        );
    }

    /**
     * Test that `getEffectiveConfig()` returns `null` if no parent or self-config exists.
     */
    public function test_get_effective_config_returns_null(): void
    {
        $tenant = Tenant::factory()->create([
            'config' => null,
            'parent_id' => null,
        ]);

        $this->assertNull($tenant->getEffectiveConfig());
    }

    /**
     * Test that the `config` attribute is properly cast to `TenantConfig`.
     */
    public function test_config_casts_to_tenant_config(): void
    {
        $tenant = Tenant::factory()->create([
            'config' => new TenantConfig(
                apiUrl: 'https://api.test.example.com',
                appUrl: 'https://app.test.example.com',
                appName: 'Test Tenant',
                appEnv: 'staging',
            ),
        ]);

        $this->assertInstanceOf(TenantConfig::class, $tenant->config);
        $this->assertEquals('https://api.test.example.com', $tenant->config->apiUrl);
        $this->assertEquals('Test Tenant', $tenant->config->appName);
    }
}
