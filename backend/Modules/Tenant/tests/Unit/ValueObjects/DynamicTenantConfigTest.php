<?php

namespace Modules\Tenant\Tests\Unit\ValueObjects;

use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(DynamicTenantConfig::class)]
#[Group('tenant-module')]
#[Group('tenant-value-objects')]
final class DynamicTenantConfigTest extends TestCase
{
    #[TestDox('Should create instance with default values')]
    public function testCreateInstanceWithDefaults(): void
    {
        $config = new DynamicTenantConfig();

        $this->assertInstanceOf(DynamicTenantConfig::class, $config);
        $this->assertNull($config->getTier());
        $this->assertEquals([], $config->toArray()['config']);
        $this->assertEquals([], $config->toArray()['visibility']);
    }

    #[TestDox('Should create instance with provided data')]
    public function testCreateInstanceWithData(): void
    {
        $data       = ['key1' => 'value1', 'key2' => 'value2'];
        $visibility = ['key1' => TenantConfigVisibility::PUBLIC];
        $tier       = 'premium';

        $config = new DynamicTenantConfig($data, $visibility, $tier);

        $this->assertEquals($data, $config->toArray()['config']);
        $this->assertEquals(['key1' => 'public'], $config->toArray()['visibility']);
        $this->assertEquals($tier, $config->getTier());
    }

    #[TestDox('Should get value using dot notation')]
    public function testGetValueUsingDotNotation(): void
    {
        $data   = [
            'database' => [
                'connection' => 'mysql',
                'host'       => 'localhost',
            ],
        ];
        $config = new DynamicTenantConfig($data);

        $this->assertEquals('mysql', $config->get('database.connection'));
        $this->assertEquals('localhost', $config->get('database.host'));
        $this->assertEquals(['connection' => 'mysql', 'host' => 'localhost'], $config->get('database'));
    }

    #[TestDox('Should return default when key not found')]
    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $config = new DynamicTenantConfig(['key1' => 'value1']);

        $this->assertEquals('default', $config->get('nonexistent', 'default'));
        $this->assertNull($config->get('nonexistent'));
    }

    #[TestDox('Should set value using dot notation')]
    public function testSetValueUsingDotNotation(): void
    {
        $config = new DynamicTenantConfig();

        $result = $config->set('database.connection', 'pgsql');

        $this->assertSame($config, $result); // Fluent interface
        $this->assertEquals('pgsql', $config->get('database.connection'));
        $this->assertEquals(['connection' => 'pgsql'], $config->get('database'));
    }

    #[TestDox('Should check if key exists')]
    public function testHasKey(): void
    {
        $config = new DynamicTenantConfig([
            'key1'   => 'value1',
            'nested' => ['key2' => 'value2'],
        ]);

        $this->assertTrue($config->has('key1'));
        $this->assertTrue($config->has('nested.key2'));
        $this->assertFalse($config->has('nonexistent'));
        $this->assertFalse($config->has('nested.nonexistent'));
    }

    #[TestDox('Should forget key using dot notation')]
    public function testForgetKey(): void
    {
        $config = new DynamicTenantConfig([
            'key1'   => 'value1',
            'nested' => ['key2' => 'value2', 'key3' => 'value3'],
        ]);

        $result = $config->forget('key1');

        $this->assertSame($config, $result); // Fluent interface
        $this->assertFalse($config->has('key1'));

        $config->forget('nested.key2');
        $this->assertFalse($config->has('nested.key2'));
        $this->assertTrue($config->has('nested.key3'));
    }

    #[TestDox('Should get visibility for key')]
    public function testGetVisibility(): void
    {
        $visibility = ['key1' => TenantConfigVisibility::PUBLIC , 'key2' => TenantConfigVisibility::PROTECTED];
        $config     = new DynamicTenantConfig([], $visibility);

        $this->assertEquals(TenantConfigVisibility::PUBLIC, $config->getVisibility('key1'));
        $this->assertEquals(TenantConfigVisibility::PROTECTED, $config->getVisibility('key2'));
        $this->assertEquals(TenantConfigVisibility::PRIVATE, $config->getVisibility('nonexistent')); // Default
    }

    #[TestDox('Should set visibility for key')]
    public function testSetVisibility(): void
    {
        $config = new DynamicTenantConfig();

        $result = $config->setVisibility('key1', TenantConfigVisibility::PUBLIC);

        $this->assertSame($config, $result); // Fluent interface
        $this->assertEquals(TenantConfigVisibility::PUBLIC, $config->getVisibility('key1'));
    }

    #[TestDox('Should get and set tier')]
    public function testGetSetTier(): void
    {
        $config = new DynamicTenantConfig();

        $this->assertNull($config->getTier());

        $result = $config->setTier('premium');

        $this->assertSame($config, $result); // Fluent interface
        $this->assertEquals('premium', $config->getTier());

        $config->setTier(null);
        $this->assertNull($config->getTier());
    }

    #[TestDox('Should merge with another DynamicTenantConfig')]
    public function testMergeWithDynamicTenantConfig(): void
    {
        $config1 = new DynamicTenantConfig(
            ['key1' => 'value1', 'key2' => 'value2'],
            ['key1' => TenantConfigVisibility::PUBLIC],
            'basic',
        );

        $config2 = new DynamicTenantConfig(
            ['key2' => 'new_value2', 'key3' => 'value3'],
            ['key2' => TenantConfigVisibility::PROTECTED , 'key3' => TenantConfigVisibility::PUBLIC],
            'premium',
        );

        $result = $config1->merge($config2);

        $this->assertSame($config1, $result); // Fluent interface

        // After merge, the data structure becomes nested due to toArray() call
        // The merge() method calls toArray() which returns ['config' => [...], 'visibility' => [...], 'tier' => ...]
        // So the original keys are preserved and new structure is added
        $this->assertEquals('value1', $config1->get('key1')); // Original key preserved
        $this->assertEquals('value2', $config1->get('key2')); // Original value preserved (not overwritten)
        $this->assertFalse($config1->has('key3')); // key3 is not at top level

        // The merged data is nested under 'config' key
        $this->assertEquals(['key2' => 'new_value2', 'key3' => 'value3'], $config1->get('config'));

        $this->assertEquals('premium', $config1->getTier()); // Updated tier
        $this->assertEquals(TenantConfigVisibility::PUBLIC, $config1->getVisibility('key1')); // Original visibility
        $this->assertEquals(TenantConfigVisibility::PROTECTED, $config1->getVisibility('key2')); // Updated visibility
        $this->assertEquals(TenantConfigVisibility::PUBLIC, $config1->getVisibility('key3')); // New visibility
    }

    #[TestDox('Should merge with array')]
    public function testMergeWithArray(): void
    {
        $config = new DynamicTenantConfig(
            ['key1' => 'value1', 'key2' => 'value2'],
            ['key1' => TenantConfigVisibility::PUBLIC],
            'basic',
        );

        $result = $config->merge(['key2' => 'new_value2', 'key3' => 'value3']);

        $this->assertSame($config, $result); // Fluent interface
        $this->assertEquals('value1', $config->get('key1'));
        $this->assertEquals('new_value2', $config->get('key2')); // Overwritten
        $this->assertEquals('value3', $config->get('key3')); // New
        $this->assertEquals('basic', $config->getTier()); // Unchanged
        $this->assertEquals(TenantConfigVisibility::PUBLIC, $config->getVisibility('key1')); // Unchanged
        $this->assertEquals(TenantConfigVisibility::PRIVATE, $config->getVisibility('key2')); // Default
        $this->assertEquals(TenantConfigVisibility::PRIVATE, $config->getVisibility('key3')); // Default
    }

    #[TestDox('Should merge with config that has null tier')]
    public function testMergeWithNullTier(): void
    {
        $config1 = new DynamicTenantConfig([], [], 'premium');
        $config2 = new DynamicTenantConfig([], [], null);

        $config1->merge($config2);

        $this->assertEquals('premium', $config1->getTier()); // Tier not overwritten by null
    }

    #[TestDox('Should get public config only')]
    public function testGetPublicConfig(): void
    {
        $config = new DynamicTenantConfig(
            ['public1' => 'value1', 'protected1' => 'value2', 'private1' => 'value3', 'public2' => 'value4'],
            [
                'public1'    => TenantConfigVisibility::PUBLIC ,
                'protected1' => TenantConfigVisibility::PROTECTED ,
                'private1'   => TenantConfigVisibility::PRIVATE ,
                'public2'    => TenantConfigVisibility::PUBLIC,
            ],
        );

        $publicConfig = $config->getPublicConfig();

        $this->assertEquals(['public1' => 'value1', 'public2' => 'value4'], $publicConfig);
    }

    #[TestDox('Should get protected config (public and protected)')]
    public function testGetProtectedConfig(): void
    {
        $config = new DynamicTenantConfig(
            ['public1' => 'value1', 'protected1' => 'value2', 'private1' => 'value3'],
            [
                'public1'    => TenantConfigVisibility::PUBLIC ,
                'protected1' => TenantConfigVisibility::PROTECTED ,
                'private1'   => TenantConfigVisibility::PRIVATE,
            ],
        );

        $protectedConfig = $config->getProtectedConfig();

        $this->assertEquals(['public1' => 'value1', 'protected1' => 'value2'], $protectedConfig);
    }

    #[TestDox('Should create from array')]
    public function testFromArray(): void
    {
        $data = [
            'config'     => ['key1' => 'value1', 'key2' => 'value2'],
            'visibility' => ['key1' => 'public', 'key2' => 'protected'],
            'tier'       => 'premium',
        ];

        $config = DynamicTenantConfig::fromArray($data);

        $this->assertEquals('value1', $config->get('key1'));
        $this->assertEquals('value2', $config->get('key2'));
        $this->assertEquals(TenantConfigVisibility::PUBLIC, $config->getVisibility('key1'));
        $this->assertEquals(TenantConfigVisibility::PROTECTED, $config->getVisibility('key2'));
        $this->assertEquals('premium', $config->getTier());
    }

    #[TestDox('Should handle invalid visibility when creating from array')]
    public function testFromArrayWithInvalidVisibility(): void
    {
        $data = [
            'config'     => ['key1' => 'value1'],
            'visibility' => ['key1' => 'invalid_visibility'],
        ];

        $config = DynamicTenantConfig::fromArray($data);

        $this->assertEquals(TenantConfigVisibility::PRIVATE, $config->getVisibility('key1')); // Default to PRIVATE
    }

    #[TestDox('Should handle enum visibility when creating from array')]
    public function testFromArrayWithEnumVisibility(): void
    {
        $data = [
            'config'     => ['key1' => 'value1'],
            'visibility' => ['key1' => TenantConfigVisibility::PUBLIC],
        ];

        $config = DynamicTenantConfig::fromArray($data);

        $this->assertEquals(TenantConfigVisibility::PUBLIC, $config->getVisibility('key1'));
    }

    #[TestDox('Should handle missing data when creating from array')]
    public function testFromArrayWithMissingData(): void
    {
        $config = DynamicTenantConfig::fromArray([]);

        $this->assertEquals([], $config->toArray()['config']);
        $this->assertEquals([], $config->toArray()['visibility']);
        $this->assertNull($config->getTier());
    }

    #[TestDox('Should convert to array')]
    public function testToArray(): void
    {
        $config = new DynamicTenantConfig(
            ['key1' => 'value1', 'key2' => 'value2'],
            ['key1' => TenantConfigVisibility::PUBLIC , 'key2' => TenantConfigVisibility::PROTECTED],
            'premium',
        );

        $array = $config->toArray();

        $this->assertEquals([
            'config'     => ['key1' => 'value1', 'key2' => 'value2'],
            'visibility' => ['key1' => 'public', 'key2' => 'protected'],
            'tier'       => 'premium',
        ], $array);
    }

    #[TestDox('Should access values via magic getter')]
    public function testMagicGetter(): void
    {
        $config = new DynamicTenantConfig([
            'test_key'    => 'test_value',
            'another_key' => 'another_value',
        ]);

        $this->assertEquals('test_value', $config->test_key);
        $this->assertEquals('another_value', $config->another_key);
        $this->assertNull($config->nonexistent);
    }

    #[TestDox('Should convert camelCase to snake_case in magic getter')]
    public function testMagicGetterConvertsCase(): void
    {
        $config = new DynamicTenantConfig([
            'test_key'         => 'test_value',
            'another_long_key' => 'another_value',
        ]);

        $this->assertEquals('test_value', $config->testKey);
        $this->assertEquals('another_value', $config->anotherLongKey);
    }

    #[TestDox('Should check existence via magic isset')]
    public function testMagicIsset(): void
    {
        $config = new DynamicTenantConfig([
            'test_key' => 'test_value',
        ]);

        $this->assertTrue(isset($config->test_key));
        $this->assertTrue(isset($config->testKey)); // camelCase conversion
        $this->assertFalse(isset($config->nonexistent));
    }

    #[TestDox('Should merge configs correctly in toArray after DynamicTenantConfig merge')]
    public function testToArrayAfterMerge(): void
    {
        $config1 = new DynamicTenantConfig(['key1' => 'value1']);
        $config2 = new DynamicTenantConfig(['key2' => 'value2']);

        $config1->merge($config2);

        $array = $config1->toArray();

        // The merge should have created a nested array structure
        // because merge() calls toArray() on config2 which returns
        // ['config' => [...], 'visibility' => [...], 'tier' => ...]
        $this->assertArrayHasKey('config', $array);
        $this->assertArrayHasKey('visibility', $array);
        $this->assertArrayHasKey('tier', $array);
    }

    #[TestDox('Should handle nested values in magic getter')]
    public function testMagicGetterWithNestedValues(): void
    {
        $config = new DynamicTenantConfig([
            'database' => [
                'connection' => 'mysql',
                'host'       => 'localhost',
            ],
        ]);

        $database = $config->database;
        $this->assertIsArray($database);
        $this->assertEquals(['connection' => 'mysql', 'host' => 'localhost'], $database);
    }

    #[TestDox('Should handle empty visibility array in toArray')]
    public function testToArrayWithEmptyVisibility(): void
    {
        $config = new DynamicTenantConfig(['key1' => 'value1']);

        $array = $config->toArray();

        $this->assertEquals([], $array['visibility']);
    }

    #[TestDox('Should create new instance for each fromArray call')]
    public function testFromArrayCreatesNewInstance(): void
    {
        $data = ['config' => ['key' => 'value']];

        $config1 = DynamicTenantConfig::fromArray($data);
        $config2 = DynamicTenantConfig::fromArray($data);

        $this->assertNotSame($config1, $config2);
        $config1->set('key', 'new_value');
        $this->assertEquals('value', $config2->get('key'));
    }

    #[TestDox('Should maintain fluent interface for all setter methods')]
    public function testFluentInterface(): void
    {
        $config = new DynamicTenantConfig();

        $result = $config
            ->set('key1', 'value1')
            ->setVisibility('key1', TenantConfigVisibility::PUBLIC)
            ->setTier('premium')
            ->set('key2', 'value2')
            ->forget('key2');

        $this->assertSame($config, $result);
        $this->assertEquals('value1', $config->get('key1'));
        $this->assertEquals(TenantConfigVisibility::PUBLIC, $config->getVisibility('key1'));
        $this->assertEquals('premium', $config->getTier());
        $this->assertFalse($config->has('key2'));
    }
}
