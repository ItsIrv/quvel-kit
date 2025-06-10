<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Modules\Tenant\Services\TenantConfigSeederRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(TenantConfigSeederRegistry::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
final class TenantConfigSeederRegistryTest extends TestCase
{
    private TenantConfigSeederRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new TenantConfigSeederRegistry();
    }

    #[TestDox('Should register seeder for specific tier')]
    public function testRegisterSeederForSpecificTier(): void
    {
        $seeder = fn($tier, $config) => ['test_key' => 'test_value'];

        $this->registry->registerSeeder('basic', $seeder, 50);

        $result = $this->registry->getSeedConfig('basic');
        $this->assertEquals(['test_key' => 'test_value'], $result);
    }

    #[TestDox('Should register seeder with custom priority')]
    public function testRegisterSeederWithCustomPriority(): void
    {
        $seeder1 = fn($tier, $config) => ['key1' => 'value1'];
        $seeder2 = fn($tier, $config) => ['key2' => 'value2'];

        // Register with different priorities (lower runs first)
        $this->registry->registerSeeder('basic', $seeder2, 100);
        $this->registry->registerSeeder('basic', $seeder1, 10);

        $result = $this->registry->getSeedConfig('basic');
        
        // Both values should be present
        $this->assertEquals('value1', $result['key1']);
        $this->assertEquals('value2', $result['key2']);
    }

    #[TestDox('Should register seeder with visibility seeder')]
    public function testRegisterSeederWithVisibilitySeeder(): void
    {
        $seeder = fn($tier, $config) => ['test_key' => 'test_value'];
        $visibilitySeeder = fn($tier, $visibility) => ['test_key' => 'public'];

        $this->registry->registerSeeder('basic', $seeder, 50, $visibilitySeeder);

        $config = $this->registry->getSeedConfig('basic');
        $visibility = $this->registry->getSeedVisibility('basic');

        $this->assertEquals(['test_key' => 'test_value'], $config);
        $this->assertEquals(['test_key' => 'public'], $visibility);
    }

    #[TestDox('Should register seeder for all templates')]
    public function testRegisterSeederForAllTemplates(): void
    {
        $seeder = fn($template, $config) => ['global_key' => "value_for_{$template}"];

        $this->registry->registerSeederForAllTemplates($seeder);

        $basicConfig = $this->registry->getSeedConfig('basic');
        $isolatedConfig = $this->registry->getSeedConfig('isolated');

        $this->assertEquals(['global_key' => 'value_for_basic'], $basicConfig);
        $this->assertEquals(['global_key' => 'value_for_isolated'], $isolatedConfig);
    }

    #[TestDox('Should register seeder for all templates with visibility')]
    public function testRegisterSeederForAllTemplatesWithVisibility(): void
    {
        $seeder = fn($template, $config) => ['global_key' => "value_for_{$template}"];
        $visibilitySeeder = fn($template, $visibility) => ['global_key' => 'private'];

        $this->registry->registerSeederForAllTemplates($seeder, 50, $visibilitySeeder);

        $basicConfig = $this->registry->getSeedConfig('basic');
        $basicVisibility = $this->registry->getSeedVisibility('basic');

        $this->assertEquals(['global_key' => 'value_for_basic'], $basicConfig);
        $this->assertEquals(['global_key' => 'private'], $basicVisibility);
    }

    #[TestDox('Should register seeder for multiple specific templates')]
    public function testRegisterSeederForMultipleTemplates(): void
    {
        $seeder = fn($template, $config) => ['multi_key' => "multi_value_{$template}"];
        $templates = ['standard', 'isolated'];

        $this->registry->registerSeederForTemplates($templates, $seeder);

        $standardConfig = $this->registry->getSeedConfig('standard');
        $isolatedConfig = $this->registry->getSeedConfig('isolated');
        $basicConfig = $this->registry->getSeedConfig('basic');

        $this->assertEquals(['multi_key' => 'multi_value_standard'], $standardConfig);
        $this->assertEquals(['multi_key' => 'multi_value_isolated'], $isolatedConfig);
        $this->assertEquals([], $basicConfig); // Should not be affected
    }

    #[TestDox('Should merge seeder config with base config')]
    public function testGetSeedConfigWithBaseConfig(): void
    {
        $seeder = fn($tier, $config) => ['seeder_key' => 'seeder_value'];
        $baseConfig = ['base_key' => 'base_value'];

        $this->registry->registerSeeder('basic', $seeder);

        $result = $this->registry->getSeedConfig('basic', $baseConfig);

        $this->assertEquals([
            'base_key' => 'base_value',
            'seeder_key' => 'seeder_value'
        ], $result);
    }

    #[TestDox('Should handle seeder returning null')]
    public function testGetSeedConfigWithSeederReturningNull(): void
    {
        $seeder = fn($tier, $config) => null;
        $baseConfig = ['base_key' => 'base_value'];

        $this->registry->registerSeeder('basic', $seeder);

        $result = $this->registry->getSeedConfig('basic', $baseConfig);

        $this->assertEquals($baseConfig, $result);
    }

    #[TestDox('Should handle seeder returning non-array')]
    public function testGetSeedConfigWithSeederReturningNonArray(): void
    {
        $seeder = fn($tier, $config) => 'invalid_return';
        $baseConfig = ['base_key' => 'base_value'];

        $this->registry->registerSeeder('basic', $seeder);

        $result = $this->registry->getSeedConfig('basic', $baseConfig);

        $this->assertEquals($baseConfig, $result);
    }

    #[TestDox('Should return base config for unknown tier')]
    public function testGetSeedConfigForUnknownTier(): void
    {
        $baseConfig = ['base_key' => 'base_value'];

        $result = $this->registry->getSeedConfig('unknown_tier', $baseConfig);

        $this->assertEquals($baseConfig, $result);
    }

    #[TestDox('Should merge visibility seeder with base visibility')]
    public function testGetSeedVisibilityWithBaseVisibility(): void
    {
        $visibilitySeeder = fn($tier, $visibility) => ['seeder_key' => 'public'];
        $baseVisibility = ['base_key' => 'private'];

        $this->registry->registerSeeder('basic', fn() => [], 50, $visibilitySeeder);

        $result = $this->registry->getSeedVisibility('basic', $baseVisibility);

        $this->assertEquals([
            'base_key' => 'private',
            'seeder_key' => 'public'
        ], $result);
    }

    #[TestDox('Should handle visibility seeder returning null')]
    public function testGetSeedVisibilityWithSeederReturningNull(): void
    {
        $visibilitySeeder = fn($tier, $visibility) => null;
        $baseVisibility = ['base_key' => 'private'];

        $this->registry->registerSeeder('basic', fn() => [], 50, $visibilitySeeder);

        $result = $this->registry->getSeedVisibility('basic', $baseVisibility);

        $this->assertEquals($baseVisibility, $result);
    }

    #[TestDox('Should return base visibility for unknown tier')]
    public function testGetSeedVisibilityForUnknownTier(): void
    {
        $baseVisibility = ['base_key' => 'private'];

        $result = $this->registry->getSeedVisibility('unknown_tier', $baseVisibility);

        $this->assertEquals($baseVisibility, $result);
    }

    #[TestDox('Should sort seeders by priority within tier')]
    public function testSeedersSortedByPriorityWithinTier(): void
    {
        $seeder1 = fn($tier, $config) => array_merge($config, ['order' => ($config['order'] ?? '') . '1']);
        $seeder2 = fn($tier, $config) => array_merge($config, ['order' => ($config['order'] ?? '') . '2']);
        $seeder3 = fn($tier, $config) => array_merge($config, ['order' => ($config['order'] ?? '') . '3']);

        // Register in reverse priority order
        $this->registry->registerSeeder('basic', $seeder3, 30);
        $this->registry->registerSeeder('basic', $seeder1, 10);
        $this->registry->registerSeeder('basic', $seeder2, 20);

        $result = $this->registry->getSeedConfig('basic');

        // Should execute in priority order: 1, 2, 3
        $this->assertEquals(['order' => '123'], $result);
    }

    #[TestDox('Should handle multiple visibility seeders sorted by priority')]
    public function testVisibilitySeedersLoadSortedByPriority(): void
    {
        $visibilitySeeder1 = fn($tier, $visibility) => array_merge($visibility, ['order' => ($visibility['order'] ?? '') . '1']);
        $visibilitySeeder2 = fn($tier, $visibility) => array_merge($visibility, ['order' => ($visibility['order'] ?? '') . '2']);

        $this->registry->registerSeeder('basic', fn() => [], 20, $visibilitySeeder2);
        $this->registry->registerSeeder('basic', fn() => [], 10, $visibilitySeeder1);

        $result = $this->registry->getSeedVisibility('basic');

        $this->assertEquals(['order' => '12'], $result);
    }

    #[TestDox('Should register seeder for custom tier')]
    public function testRegisterSeederForCustomTier(): void
    {
        $seeder = fn($tier, $config) => ['custom_key' => 'custom_value'];

        $this->registry->registerSeeder('custom_tier', $seeder);

        $result = $this->registry->getSeedConfig('custom_tier');
        $this->assertEquals(['custom_key' => 'custom_value'], $result);
    }

    #[TestDox('Should handle seeder that modifies existing config values')]
    public function testSeederModifyingExistingConfigValues(): void
    {
        $seeder1 = fn($tier, $config) => ['shared_key' => 'first_value', 'unique1' => 'value1'];
        $seeder2 = fn($tier, $config) => array_merge($config, ['shared_key' => 'overridden_value', 'unique2' => 'value2']);

        $this->registry->registerSeeder('basic', $seeder1, 10);
        $this->registry->registerSeeder('basic', $seeder2, 20);

        $result = $this->registry->getSeedConfig('basic');

        $this->assertEquals([
            'shared_key' => 'overridden_value',
            'unique1' => 'value1',
            'unique2' => 'value2'
        ], $result);
    }
}