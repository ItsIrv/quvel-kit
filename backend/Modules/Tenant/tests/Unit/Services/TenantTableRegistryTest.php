<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Modules\Tenant\Services\TenantTableRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(TenantTableRegistry::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
final class TenantTableRegistryTest extends TestCase
{
    private TenantTableRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new TenantTableRegistry();
    }

    #[TestDox('Should register table with default configuration')]
    public function testRegisterTableWithDefaultConfig(): void
    {
        $this->registry->registerTable('users');

        $config = $this->registry->getTableConfig('users');

        $this->assertNotNull($config);
        $this->assertEquals('id', $config['after']);
        $this->assertTrue($config['cascade_delete']);
        $this->assertEquals([], $config['drop_uniques']);
        $this->assertEquals([], $config['tenant_unique_constraints']);
    }

    #[TestDox('Should register table with custom configuration')]
    public function testRegisterTableWithCustomConfig(): void
    {
        $customConfig = [
            'after' => 'created_at',
            'cascade_delete' => false,
            'drop_uniques' => ['email_unique'],
            'tenant_unique_constraints' => ['email', 'username']
        ];

        $this->registry->registerTable('users', $customConfig);

        $config = $this->registry->getTableConfig('users');

        $this->assertEquals('created_at', $config['after']);
        $this->assertFalse($config['cascade_delete']);
        $this->assertEquals(['email_unique'], $config['drop_uniques']);
        $this->assertEquals(['email', 'username'], $config['tenant_unique_constraints']);
    }

    #[TestDox('Should merge custom config with default config')]
    public function testRegisterTableMergesWithDefaultConfig(): void
    {
        $partialConfig = [
            'after' => 'custom_column',
            'drop_uniques' => ['index1', 'index2']
        ];

        $this->registry->registerTable('posts', $partialConfig);

        $config = $this->registry->getTableConfig('posts');

        $this->assertEquals('custom_column', $config['after']);
        $this->assertTrue($config['cascade_delete']); // Default value preserved
        $this->assertEquals(['index1', 'index2'], $config['drop_uniques']);
        $this->assertEquals([], $config['tenant_unique_constraints']); // Default value preserved
    }

    #[TestDox('Should register multiple tables at once')]
    public function testRegisterMultipleTables(): void
    {
        $tables = [
            'users' => [
                'after' => 'email',
                'cascade_delete' => false
            ],
            'posts' => [
                'drop_uniques' => ['title_unique'],
                'tenant_unique_constraints' => ['slug']
            ],
            'comments' => [] // Empty config, should use defaults
        ];

        $this->registry->registerTables($tables);

        // Check users table
        $usersConfig = $this->registry->getTableConfig('users');
        $this->assertEquals('email', $usersConfig['after']);
        $this->assertFalse($usersConfig['cascade_delete']);

        // Check posts table
        $postsConfig = $this->registry->getTableConfig('posts');
        $this->assertEquals(['title_unique'], $postsConfig['drop_uniques']);
        $this->assertEquals(['slug'], $postsConfig['tenant_unique_constraints']);

        // Check comments table (defaults)
        $commentsConfig = $this->registry->getTableConfig('comments');
        $this->assertEquals('id', $commentsConfig['after']);
        $this->assertTrue($commentsConfig['cascade_delete']);
    }

    #[TestDox('Should get all registered tables')]
    public function testGetTables(): void
    {
        $this->registry->registerTable('users', ['after' => 'email']);
        $this->registry->registerTable('posts', ['drop_uniques' => ['title_unique']]);

        $tables = $this->registry->getTables();

        $this->assertCount(2, $tables);
        $this->assertArrayHasKey('users', $tables);
        $this->assertArrayHasKey('posts', $tables);
        $this->assertEquals('email', $tables['users']['after']);
        $this->assertEquals(['title_unique'], $tables['posts']['drop_uniques']);
    }

    #[TestDox('Should return null for non-existent table config')]
    public function testGetTableConfigForNonExistentTable(): void
    {
        $config = $this->registry->getTableConfig('non_existent_table');

        $this->assertNull($config);
    }

    #[TestDox('Should check if table is registered')]
    public function testHasTable(): void
    {
        $this->registry->registerTable('users');

        $this->assertTrue($this->registry->hasTable('users'));
        $this->assertFalse($this->registry->hasTable('non_existent_table'));
    }

    #[TestDox('Should overwrite existing table configuration')]
    public function testRegisterTableOverwritesExisting(): void
    {
        // Register table first time
        $this->registry->registerTable('users', ['after' => 'email']);

        // Register same table with different config
        $this->registry->registerTable('users', ['after' => 'username', 'cascade_delete' => false]);

        $config = $this->registry->getTableConfig('users');

        $this->assertEquals('username', $config['after']);
        $this->assertFalse($config['cascade_delete']);
    }

    #[TestDox('Should handle empty table name')]
    public function testRegisterEmptyTableName(): void
    {
        $this->registry->registerTable('');

        $this->assertTrue($this->registry->hasTable(''));
        $this->assertNotNull($this->registry->getTableConfig(''));
    }

    #[TestDox('Should handle complex configuration structures')]
    public function testRegisterTableWithComplexConfig(): void
    {
        $complexConfig = [
            'after' => 'uuid',
            'cascade_delete' => true,
            'drop_uniques' => ['email_domain_unique', 'username_tenant_unique'],
            'tenant_unique_constraints' => ['email', 'username', 'slug'],
            'custom_field' => 'custom_value', // Non-default field
            'nested' => [
                'level1' => [
                    'level2' => 'deep_value'
                ]
            ]
        ];

        $this->registry->registerTable('complex_table', $complexConfig);

        $config = $this->registry->getTableConfig('complex_table');

        $this->assertEquals('uuid', $config['after']);
        $this->assertEquals(['email_domain_unique', 'username_tenant_unique'], $config['drop_uniques']);
        $this->assertEquals(['email', 'username', 'slug'], $config['tenant_unique_constraints']);
        $this->assertEquals('custom_value', $config['custom_field']);
        $this->assertEquals('deep_value', $config['nested']['level1']['level2']);
    }

    #[TestDox('Should preserve all default config keys when registering empty config')]
    public function testRegisterTableWithEmptyConfigPreservesDefaults(): void
    {
        $this->registry->registerTable('empty_config_table', []);

        $config = $this->registry->getTableConfig('empty_config_table');

        $this->assertArrayHasKey('after', $config);
        $this->assertArrayHasKey('cascade_delete', $config);
        $this->assertArrayHasKey('drop_uniques', $config);
        $this->assertArrayHasKey('tenant_unique_constraints', $config);

        $this->assertEquals('id', $config['after']);
        $this->assertTrue($config['cascade_delete']);
        $this->assertEquals([], $config['drop_uniques']);
        $this->assertEquals([], $config['tenant_unique_constraints']);
    }

    #[TestDox('Should handle numeric table names')]
    public function testRegisterNumericTableName(): void
    {
        $this->registry->registerTable('123_table', ['after' => 'created_at']);

        $this->assertTrue($this->registry->hasTable('123_table'));
        $config = $this->registry->getTableConfig('123_table');
        $this->assertEquals('created_at', $config['after']);
    }

    #[TestDox('Should handle special characters in table names')]
    public function testRegisterTableWithSpecialCharacters(): void
    {
        $tableName = 'table_with-special.chars';
        $this->registry->registerTable($tableName, ['cascade_delete' => false]);

        $this->assertTrue($this->registry->hasTable($tableName));
        $config = $this->registry->getTableConfig($tableName);
        $this->assertFalse($config['cascade_delete']);
    }

    #[TestDox('Should maintain independent configs for different tables')]
    public function testIndependentTableConfigs(): void
    {
        $this->registry->registerTable('table1', ['after' => 'field1', 'cascade_delete' => true]);
        $this->registry->registerTable('table2', ['after' => 'field2', 'cascade_delete' => false]);

        $config1 = $this->registry->getTableConfig('table1');
        $config2 = $this->registry->getTableConfig('table2');

        $this->assertEquals('field1', $config1['after']);
        $this->assertTrue($config1['cascade_delete']);

        $this->assertEquals('field2', $config2['after']);
        $this->assertFalse($config2['cascade_delete']);
    }
}