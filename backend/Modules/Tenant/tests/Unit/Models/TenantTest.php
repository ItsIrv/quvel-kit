<?php

namespace Modules\Tenant\Tests\Unit\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Database\Factories\TenantFactory;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\database\factories\DynamicTenantConfigFactory;
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
    public function testHasFillableProperties(): void
    {
        $tenant = new Tenant();

        $this->assertEquals(
            ['name', 'domain', 'parent_id', 'config'],
            $tenant->getFillable(),
        );
    }

    /**
     * Test that the Tenant model can create a new factory instance.
     */
    public function testCanCreateNewFactoryInstance(): void
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
    public function testParentRelationship(): void
    {
        $tenant = new Tenant();

        $this->assertInstanceOf(BelongsTo::class, $tenant->parent());
    }

    /**
     * Test that the children relationship returns a HasMany instance.
     */
    public function testChildrenRelationship(): void
    {
        $tenant = new Tenant();

        $this->assertInstanceOf(HasMany::class, $tenant->children());
    }

    /**
     * Test that the users relationship returns a HasMany instance.
     */
    public function testUsersRelationship(): void
    {
        $tenant = new Tenant();

        $this->assertInstanceOf(HasMany::class, $tenant->users());
    }

    /**
     * Test that a tenant can be created.
     */
    public function testCreatingTenant(): void
    {
        $tenant = Tenant::factory()->create([
            'name'   => 'Example Tenant',
            'domain' => 'example.com',
        ]);

        $this->assertDatabaseHas('tenants', [
            'id'     => $tenant->id,
            'name'   => 'Example Tenant',
            'domain' => 'example.com',
        ]);

        $this->assertInstanceOf(Tenant::class, $tenant);
    }

    /**
     * Test that `getEffectiveConfig()` correctly falls back to the parent's config.
     */
    public function testGetEffectiveConfigInheritsParentConfig(): void
    {
        // Create a parent tenant with a config
        $parentConfig = DynamicTenantConfigFactory::createStandard(
            apiDomain: 'api.example.com',
            appName: 'Example App'
        );

        $parentTenant = Tenant::factory()->create([
            'config' => $parentConfig,
        ]);

        // Create a child tenant that does not have its own config
        $childTenant = Tenant::factory()->create([
            'parent_id' => $parentTenant->id,
            'config'    => null, // No direct config
        ]);

        // Ensure the child's effective config is inherited from the parent
        $this->assertInstanceOf(
            DynamicTenantConfig::class,
            $childTenant->getEffectiveConfig(),
        );

        $this->assertEquals(
            'api.example.com',
            $childTenant->getEffectiveConfig()->get('domain'),
        );

        $this->assertEquals(
            'api.example.com',
            $childTenant->getEffectiveConfig()->get('domain'),
        );
    }

    /**
     * Test that `getEffectiveConfig()` returns its own config when no parent exists.
     */
    public function testGetEffectiveConfigReturnsOwnConfig(): void
    {
        $config = DynamicTenantConfigFactory::createStandard(
            apiDomain: 'api.example.com',
            appName: 'Example App'
        );

        $tenant = Tenant::factory()->create([
            'config' => $config,
        ]);

        $this->assertInstanceOf(
            DynamicTenantConfig::class,
            $tenant->getEffectiveConfig(),
        );

        $this->assertEquals(
            'api.example.com',
            $tenant->getEffectiveConfig()->get('domain'),
        );
    }

    /**
     * Test that `getEffectiveConfig()` returns `null` if no parent or self-config exists.
     */
    public function testGetEffectiveConfigReturnsNull(): void
    {
        $tenant = Tenant::factory()->create([
            'config'    => null,
            'parent_id' => null,
        ]);

        $this->assertNull($tenant->getEffectiveConfig());
    }

    /**
     * Test that `getEffectiveConfig()` correctly merges parent and child configs.
     */
    public function testGetEffectiveConfigMergesParentAndChildConfigs(): void
    {
        // Create a parent tenant with a config
        $parentConfig = new DynamicTenantConfig([
            'domain' => 'parent.example.com',
            'theme' => 'dark',
            'features' => ['feature1', 'feature2'],
            'shared_setting' => 'parent_value'
        ], [], 'basic');

        $parentTenant = Tenant::factory()->create([
            'config' => $parentConfig,
        ]);

        // Create a child tenant with its own config
        $childConfig = new DynamicTenantConfig([
            'domain' => 'child.example.com',
            'logo' => 'custom_logo.png',
            'shared_setting' => 'child_value'
        ], [], 'premium');

        $childTenant = Tenant::factory()->create([
            'parent_id' => $parentTenant->id,
            'config' => $childConfig,
        ]);

        // Refresh to ensure relationships are loaded
        $childTenant->refresh();

        // Get the effective config
        $effectiveConfig = $childTenant->getEffectiveConfig();

        // Assert the config is merged correctly
        $this->assertInstanceOf(DynamicTenantConfig::class, $effectiveConfig);

        // Child-specific values should be preserved
        $this->assertEquals('child.example.com', $effectiveConfig->get('domain'));
        $this->assertEquals('custom_logo.png', $effectiveConfig->get('logo'));

        // Parent values should be inherited when not overridden
        $this->assertEquals('dark', $effectiveConfig->get('theme'));
        $this->assertEquals(['feature1', 'feature2'], $effectiveConfig->get('features'));

        // Child values should override parent values for shared settings
        $this->assertEquals('child_value', $effectiveConfig->get('shared_setting'));

        // Child tier should take precedence
        $this->assertEquals('premium', $effectiveConfig->getTier());
    }

    /**
     * Test that `getEffectiveConfig()` preserves parent tier when child has no tier set.
     */
    public function testGetEffectiveConfigPreservesParentTierWhenChildHasNoTier(): void
    {
        // Create a parent tenant with a config and tier
        $parentConfig = new DynamicTenantConfig([
            'domain' => 'parent.example.com'
        ], [], 'enterprise');

        $parentTenant = Tenant::factory()->create([
            'config' => $parentConfig,
        ]);

        // Create a child tenant with config but no tier
        $childConfig = new DynamicTenantConfig([
            'domain' => 'child.example.com'
        ]);
        // Explicitly not setting a tier

        $childTenant = Tenant::factory()->create([
            'parent_id' => $parentTenant->id,
            'config' => $childConfig,
        ]);

        // Refresh to ensure relationships are loaded
        $childTenant->refresh();

        // Get the effective config
        $effectiveConfig = $childTenant->getEffectiveConfig();

        // Assert parent tier is preserved
        $this->assertEquals('enterprise', $effectiveConfig->getTier());

        // Child-specific values should still override parent values
        $this->assertEquals('child.example.com', $effectiveConfig->get('domain'));
    }

    /**
     * Test that the `config` attribute is properly cast to `DynamicTenantConfig`.
     */
    public function testConfigCastsToTenantConfig(): void
    {
        $config = DynamicTenantConfigFactory::createStandard(
            apiDomain: 'api.example.com',
            appName: 'Example App'
        );

        $tenant = Tenant::factory()->create([
            'config' => $config,
        ]);

        $this->assertInstanceOf(DynamicTenantConfig::class, $tenant->config);
        $this->assertEquals('api.example.com', $tenant->config->get('domain'));
    }
}
