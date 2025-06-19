<?php

namespace Modules\Tenant\Tests\Unit\Factories;

use Modules\Tenant\Factories\TenantConfigFactory;
use Modules\Tenant\Contracts\TenantConfigSeederInterface;
use Modules\Tenant\ValueObjects\TenantExclusionConfig;
use Modules\Tenant\ValueObjects\TenantTableConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantConfigFactory::class)]
#[Group('tenant-module')]
#[Group('tenant-factories')]
class TenantConfigFactoryTest extends TestCase
{
    /**
     * Test table() method creates single table configuration.
     */
    public function testTableCreatesSingleTableConfiguration(): void
    {
        $config = TenantConfigFactory::table('users', function ($builder) {
            $builder->after('id')->cascadeDelete(true);
        });

        $this->assertIsArray($config);
        $this->assertArrayHasKey('users', $config);
        $this->assertInstanceOf(TenantTableConfig::class, $config['users']);

        $tableConfig = $config['users'];
        $this->assertEquals('id', $tableConfig->after);
        $this->assertTrue($tableConfig->cascadeDelete);
    }

    /**
     * Test table() method with null callback uses defaults.
     */
    public function testTableWithNullCallbackUsesDefaults(): void
    {
        $config = TenantConfigFactory::table('products');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('products', $config);
        $this->assertInstanceOf(TenantTableConfig::class, $config['products']);

        $tableConfig = $config['products'];
        $this->assertEquals('id', $tableConfig->after); // Default value
        $this->assertTrue($tableConfig->cascadeDelete); // Default value
    }

    /**
     * Test table() method with complex configuration.
     */
    public function testTableWithComplexConfiguration(): void
    {
        $config = TenantConfigFactory::table('orders', function ($builder) {
            $builder
                ->after('tenant_id')
                ->cascadeDelete(false)
                ->dropUniques([['email'], ['slug']])
                ->tenantUniques([
                    ['columns' => ['email'], 'name' => 'tenant_email_unique'],
                    ['columns' => ['slug'], 'name' => 'tenant_slug_unique'],
                ]);
        });

        $tableConfig = $config['orders'];
        $this->assertEquals('tenant_id', $tableConfig->after);
        $this->assertFalse($tableConfig->cascadeDelete);
        $this->assertEquals([['email'], ['slug']], $tableConfig->dropUniques);
        $this->assertCount(2, $tableConfig->tenantUniqueConstraints);
    }

    /**
     * Test tables() method with callable configurations.
     */
    public function testTablesWithCallableConfigurations(): void
    {
        $configs = TenantConfigFactory::tables([
            'users' => function ($builder) {
                $builder->after('id')->cascadeDelete(true);
            },
            'posts' => function ($builder) {
                $builder->after('user_id')->cascadeDelete(false);
            },
        ]);

        $this->assertIsArray($configs);
        $this->assertArrayHasKey('users', $configs);
        $this->assertArrayHasKey('posts', $configs);

        $this->assertInstanceOf(TenantTableConfig::class, $configs['users']);
        $this->assertInstanceOf(TenantTableConfig::class, $configs['posts']);

        $this->assertEquals('id', $configs['users']->after);
        $this->assertTrue($configs['users']->cascadeDelete);

        $this->assertEquals('user_id', $configs['posts']->after);
        $this->assertFalse($configs['posts']->cascadeDelete);
    }

    /**
     * Test tables() method with TenantTableConfig objects.
     */
    public function testTablesWithTenantTableConfigObjects(): void
    {
        $userConfig = TenantTableConfig::fromArray(['after' => 'id', 'cascade_delete' => true]);
        $postConfig = TenantTableConfig::fromArray(['after' => 'user_id', 'cascade_delete' => false]);

        $configs = TenantConfigFactory::tables([
            'users' => $userConfig,
            'posts' => $postConfig,
        ]);

        $this->assertSame($userConfig, $configs['users']);
        $this->assertSame($postConfig, $configs['posts']);
    }

    /**
     * Test tables() method with array configurations.
     */
    public function testTablesWithArrayConfigurations(): void
    {
        $configs = TenantConfigFactory::tables([
            'users' => ['after' => 'id', 'cascade_delete' => true],
            'posts' => ['after' => 'user_id', 'cascade_delete' => false],
        ]);

        $this->assertIsArray($configs);
        $this->assertInstanceOf(TenantTableConfig::class, $configs['users']);
        $this->assertInstanceOf(TenantTableConfig::class, $configs['posts']);

        $this->assertEquals('id', $configs['users']->after);
        $this->assertTrue($configs['users']->cascadeDelete);

        $this->assertEquals('user_id', $configs['posts']->after);
        $this->assertFalse($configs['posts']->cascadeDelete);
    }

    /**
     * Test tables() method with mixed configuration types.
     */
    public function testTablesWithMixedConfigurationTypes(): void
    {
        $configObject = TenantTableConfig::fromArray(['after' => 'tenant_id']);

        $configs = TenantConfigFactory::tables([
            'users' => function ($builder) {
                $builder->after('id');
            },
            'posts' => $configObject,
            'comments' => ['after' => 'post_id', 'cascade_delete' => false],
        ]);

        $this->assertCount(3, $configs);
        $this->assertInstanceOf(TenantTableConfig::class, $configs['users']);
        $this->assertSame($configObject, $configs['posts']);
        $this->assertInstanceOf(TenantTableConfig::class, $configs['comments']);
    }

    /**
     * Test seeder() method creates configuration seeder.
     */
    public function testSeederCreatesConfigurationSeeder(): void
    {
        $seeder = TenantConfigFactory::seeder('basic', function ($builder) {
            $builder
                ->set('app_name', 'Test App')
                ->set('app_url', 'https://test.example.com')
                ->public('app_name')
                ->private('app_url')
                ->priority(25);
        });

        $this->assertInstanceOf(TenantConfigSeederInterface::class, $seeder);

        $config = $seeder->getConfig('basic', []);
        $this->assertEquals('Test App', $config['app_name']);
        $this->assertEquals('https://test.example.com', $config['app_url']);

        $visibility = $seeder->getVisibility();
        $this->assertEquals('public', $visibility['app_name']);
        $this->assertEquals('private', $visibility['app_url']);

        $this->assertEquals(25, $seeder->getPriority());
    }

    /**
     * Test exclusions() method creates exclusion configuration.
     */
    public function testExclusionsCreatesExclusionConfiguration(): void
    {
        $exclusions = TenantConfigFactory::exclusions(function ($builder) {
            $builder
                ->path('/api/system')
                ->path('/admin/global')
                ->pattern('/api/v*/system/*')
                ->pattern('/webhooks/*');
        });

        $this->assertInstanceOf(TenantExclusionConfig::class, $exclusions);

        $this->assertContains('/api/system', $exclusions->paths);
        $this->assertContains('/admin/global', $exclusions->paths);
        $this->assertContains('/api/v*/system/*', $exclusions->patterns);
        $this->assertContains('/webhooks/*', $exclusions->patterns);
    }

    /**
     * Test simpleSeeder() method with all parameters.
     */
    public function testSimpleSeederWithAllParameters(): void
    {
        $config = ['app_name' => 'Simple App', 'debug' => true];
        $visibility = ['app_name' => 'public', 'debug' => 'private'];
        $priority = 75;

        $seeder = TenantConfigFactory::simpleSeeder($config, $visibility, $priority);

        $this->assertInstanceOf(TenantConfigSeederInterface::class, $seeder);
        $this->assertEquals($config, $seeder->getConfig('basic', []));
        $this->assertEquals($visibility, $seeder->getVisibility());
        $this->assertEquals($priority, $seeder->getPriority());
    }

    /**
     * Test simpleSeeder() method with default parameters.
     */
    public function testSimpleSeederWithDefaultParameters(): void
    {
        $config = ['app_name' => 'Default App'];

        $seeder = TenantConfigFactory::simpleSeeder($config);

        $this->assertEquals($config, $seeder->getConfig('basic', []));
        $this->assertEquals([], $seeder->getVisibility());
        $this->assertEquals(50, $seeder->getPriority()); // Default priority
    }

    /**
     * Test simpleSeeder() method with empty config.
     */
    public function testSimpleSeederWithEmptyConfig(): void
    {
        $seeder = TenantConfigFactory::simpleSeeder([]);

        $this->assertEquals([], $seeder->getConfig('basic', []));
        $this->assertEquals([], $seeder->getVisibility());
        $this->assertEquals(50, $seeder->getPriority());
    }

    /**
     * Test simpleExclusions() method with both paths and patterns.
     */
    public function testSimpleExclusionsWithBothPathsAndPatterns(): void
    {
        $paths = ['/api/system', '/admin/global'];
        $patterns = ['/api/v*/system/*', '/webhooks/*'];

        $exclusions = TenantConfigFactory::simpleExclusions($paths, $patterns);

        $this->assertInstanceOf(TenantExclusionConfig::class, $exclusions);
        $this->assertEquals($paths, $exclusions->paths);
        $this->assertEquals($patterns, $exclusions->patterns);
    }

    /**
     * Test simpleExclusions() method with only paths.
     */
    public function testSimpleExclusionsWithOnlyPaths(): void
    {
        $paths = ['/api/system', '/admin/global'];

        $exclusions = TenantConfigFactory::simpleExclusions($paths);

        $this->assertEquals($paths, $exclusions->paths);
        $this->assertEquals([], $exclusions->patterns);
    }

    /**
     * Test simpleExclusions() method with only patterns.
     */
    public function testSimpleExclusionsWithOnlyPatterns(): void
    {
        $patterns = ['/api/v*/system/*', '/webhooks/*'];

        $exclusions = TenantConfigFactory::simpleExclusions([], $patterns);

        $this->assertEquals([], $exclusions->paths);
        $this->assertEquals($patterns, $exclusions->patterns);
    }

    /**
     * Test simpleExclusions() method with empty arrays.
     */
    public function testSimpleExclusionsWithEmptyArrays(): void
    {
        $exclusions = TenantConfigFactory::simpleExclusions();

        $this->assertEquals([], $exclusions->paths);
        $this->assertEquals([], $exclusions->patterns);
    }

    /**
     * Test tableFromArray() method creates table configuration.
     */
    public function testTableFromArrayCreatesTableConfiguration(): void
    {
        $configArray = [
            'after' => 'tenant_id',
            'cascade_delete' => false,
            'drop_uniques' => [['email']],
            'tenant_unique_constraints' => [
                ['columns' => ['email'], 'name' => 'tenant_email_unique']
            ]
        ];

        $config = TenantConfigFactory::tableFromArray('users', $configArray);

        $this->assertIsArray($config);
        $this->assertArrayHasKey('users', $config);
        $this->assertInstanceOf(TenantTableConfig::class, $config['users']);

        $tableConfig = $config['users'];
        $this->assertEquals('tenant_id', $tableConfig->after);
        $this->assertFalse($tableConfig->cascadeDelete);
        $this->assertEquals([['email']], $tableConfig->dropUniques);
        $this->assertCount(1, $tableConfig->tenantUniqueConstraints);
    }

    /**
     * Test tableFromArray() method with empty configuration.
     */
    public function testTableFromArrayWithEmptyConfiguration(): void
    {
        $config = TenantConfigFactory::tableFromArray('empty_table', []);

        $this->assertArrayHasKey('empty_table', $config);
        $this->assertInstanceOf(TenantTableConfig::class, $config['empty_table']);

        $tableConfig = $config['empty_table'];
        $this->assertEquals('id', $tableConfig->after); // Default value
        $this->assertTrue($tableConfig->cascadeDelete); // Default value
    }

    /**
     * Test complex integration scenario.
     */
    public function testComplexIntegrationScenario(): void
    {
        // Create tables with different configuration methods
        $tableConfigs = TenantConfigFactory::tables([
            'users' => function ($builder) {
                $builder->after('id')->cascadeDelete(true);
            },
            'posts' => ['after' => 'user_id', 'cascade_delete' => false],
        ]);

        // Create a seeder
        $seeder = TenantConfigFactory::seeder('isolated', function ($builder) {
            $builder
                ->config(['app_name' => 'Complex App'])
                ->public('app_name')
                ->priority(100);
        });

        // Create exclusions
        $exclusions = TenantConfigFactory::simpleExclusions(
            ['/api/system'],
            ['/webhooks/*']
        );

        // Verify all components work together
        $this->assertCount(2, $tableConfigs);
        $this->assertInstanceOf(TenantConfigSeederInterface::class, $seeder);
        $this->assertInstanceOf(TenantExclusionConfig::class, $exclusions);

        $this->assertEquals('Complex App', $seeder->getConfig('isolated', [])['app_name']);
        $this->assertContains('/api/system', $exclusions->paths);
        $this->assertContains('/webhooks/*', $exclusions->patterns);
    }
}
