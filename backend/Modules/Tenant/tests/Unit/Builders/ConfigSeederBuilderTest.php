<?php

namespace Modules\Tenant\Tests\Unit\Builders;

use Modules\Tenant\Builders\ConfigSeederBuilder;
use Modules\Tenant\Contracts\ConfigSeederBuilderInterface;
use Modules\Tenant\Contracts\TenantConfigSeederInterface;
use Modules\Tenant\Seeders\GenericTenantSeeder;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(ConfigSeederBuilder::class)]
#[Group('tenant-module')]
#[Group('tenant-builders')]
class ConfigSeederBuilderTest extends TestCase
{
    /**
     * Test that create() returns a new instance.
     */
    public function testCreateReturnsNewInstance(): void
    {
        $builder = ConfigSeederBuilder::create();

        $this->assertInstanceOf(ConfigSeederBuilder::class, $builder);
        $this->assertInstanceOf(ConfigSeederBuilderInterface::class, $builder);
    }

    /**
     * Test that create() returns different instances.
     */
    public function testCreateReturnsDifferentInstances(): void
    {
        $builder1 = ConfigSeederBuilder::create();
        $builder2 = ConfigSeederBuilder::create();

        $this->assertNotSame($builder1, $builder2);
    }

    /**
     * Test config() method sets configuration and returns fluent interface.
     */
    public function testConfigSetsConfigurationAndReturnsFluentInterface(): void
    {
        $builder = ConfigSeederBuilder::create();
        $config = ['app_name' => 'Test App', 'app_url' => 'https://test.example.com'];
        $result = $builder->config($config);

        $this->assertSame($builder, $result);

        $seeder = $builder->build();
        $seederConfig = $seeder->getConfig('basic', []);

        $this->assertEquals('Test App', $seederConfig['app_name']);
        $this->assertEquals('https://test.example.com', $seederConfig['app_url']);
    }

    /**
     * Test config() method merges with existing configuration.
     */
    public function testConfigMergesWithExistingConfiguration(): void
    {
        $builder = ConfigSeederBuilder::create();
        $config1 = ['app_name' => 'Test App'];
        $config2 = ['app_url' => 'https://test.example.com'];
        $config3 = ['app_name' => 'Updated App', 'debug' => true]; // Override app_name

        $builder->config($config1)->config($config2)->config($config3);

        $seeder = $builder->build();
        $seederConfig = $seeder->getConfig('basic', []);

        $this->assertEquals('Updated App', $seederConfig['app_name']); // Overridden
        $this->assertEquals('https://test.example.com', $seederConfig['app_url']); // From config2
        $this->assertTrue($seederConfig['debug']); // From config3
    }

    /**
     * Test visibility() method sets visibility and returns fluent interface.
     */
    public function testVisibilitySetsVisibilityAndReturnsFluentInterface(): void
    {
        $builder = ConfigSeederBuilder::create();
        $visibility = ['app_name' => 'public', 'secret_key' => 'private'];
        $result = $builder->visibility($visibility);

        $this->assertSame($builder, $result);

        $seeder = $builder->build();
        $seederVisibility = $seeder->getVisibility();

        $this->assertEquals('public', $seederVisibility['app_name']);
        $this->assertEquals('private', $seederVisibility['secret_key']);
    }

    /**
     * Test visibility() method merges with existing visibility.
     */
    public function testVisibilityMergesWithExistingVisibility(): void
    {
        $builder = ConfigSeederBuilder::create();
        $visibility1 = ['app_name' => 'public'];
        $visibility2 = ['secret_key' => 'private'];
        $visibility3 = ['app_name' => 'protected', 'debug' => 'public']; // Override app_name

        $builder->visibility($visibility1)->visibility($visibility2)->visibility($visibility3);

        $seeder = $builder->build();
        $seederVisibility = $seeder->getVisibility();

        $this->assertEquals('protected', $seederVisibility['app_name']); // Overridden
        $this->assertEquals('private', $seederVisibility['secret_key']); // From visibility2
        $this->assertEquals('public', $seederVisibility['debug']); // From visibility3
    }

    /**
     * Test priority() method sets priority and returns fluent interface.
     */
    public function testPrioritySetsPriorityAndReturnsFluentInterface(): void
    {
        $builder = ConfigSeederBuilder::create();
        $result = $builder->priority(100);

        $this->assertSame($builder, $result);

        $seeder = $builder->build();
        $this->assertEquals(100, $seeder->getPriority());
    }

    /**
     * Test priority() method with different values.
     */
    public function testPriorityWithDifferentValues(): void
    {
        $testCases = [0, 1, 50, 100, 999, -10];

        foreach ($testCases as $priority) {
            $builder = ConfigSeederBuilder::create()->priority($priority);
            $seeder = $builder->build();

            $this->assertEquals($priority, $seeder->getPriority());
        }
    }

    /**
     * Test configs() method processes multiple config arrays.
     */
    public function testConfigsProcessesMultipleConfigArrays(): void
    {
        $builder = ConfigSeederBuilder::create();
        $configs = [
            ['app_name' => 'Test App'],
            ['app_url' => 'https://test.example.com'],
            ['debug' => true],
        ];
        $result = $builder->configs($configs);

        $this->assertSame($builder, $result);

        $seeder = $builder->build();
        $seederConfig = $seeder->getConfig('basic', []);

        $this->assertEquals('Test App', $seederConfig['app_name']);
        $this->assertEquals('https://test.example.com', $seederConfig['app_url']);
        $this->assertTrue($seederConfig['debug']);
    }

    /**
     * Test configs() method with empty array.
     */
    public function testConfigsWithEmptyArray(): void
    {
        $builder = ConfigSeederBuilder::create();
        $result = $builder->configs([]);

        $this->assertSame($builder, $result);

        $seeder = $builder->build();
        $seederConfig = $seeder->getConfig('basic', []);

        $this->assertEquals([], $seederConfig);
    }

    /**
     * Test set() method sets single configuration value.
     */
    public function testSetSetsSingleConfigurationValue(): void
    {
        $builder = ConfigSeederBuilder::create();
        $result = $builder->set('app_name', 'Test Application');

        $this->assertSame($builder, $result);

        $seeder = $builder->build();
        $seederConfig = $seeder->getConfig('basic', []);

        $this->assertEquals('Test Application', $seederConfig['app_name']);
    }

    /**
     * Test set() method with different value types.
     */
    public function testSetWithDifferentValueTypes(): void
    {
        $builder = ConfigSeederBuilder::create();

        $builder
            ->set('string_value', 'test')
            ->set('int_value', 42)
            ->set('bool_value', true)
            ->set('array_value', ['a', 'b', 'c'])
            ->set('null_value', null);

        $seeder = $builder->build();
        $seederConfig = $seeder->getConfig('basic', []);

        $this->assertEquals('test', $seederConfig['string_value']);
        $this->assertEquals(42, $seederConfig['int_value']);
        $this->assertTrue($seederConfig['bool_value']);
        $this->assertEquals(['a', 'b', 'c'], $seederConfig['array_value']);
        $this->assertNull($seederConfig['null_value']);
    }

    /**
     * Test set() method overwrites existing values.
     */
    public function testSetOverwritesExistingValues(): void
    {
        $builder = ConfigSeederBuilder::create();

        $builder
            ->set('app_name', 'Original Name')
            ->set('app_name', 'Updated Name');

        $seeder = $builder->build();
        $seederConfig = $seeder->getConfig('basic', []);

        $this->assertEquals('Updated Name', $seederConfig['app_name']);
    }

    /**
     * Test visible() method sets single visibility value.
     */
    public function testVisibleSetsSingleVisibilityValue(): void
    {
        $builder = ConfigSeederBuilder::create();
        $result = $builder->visible('app_name', 'public');

        $this->assertSame($builder, $result);

        $seeder = $builder->build();
        $seederVisibility = $seeder->getVisibility();

        $this->assertEquals('public', $seederVisibility['app_name']);
    }

    /**
     * Test visible() method with different visibility levels.
     */
    public function testVisibleWithDifferentVisibilityLevels(): void
    {
        $builder = ConfigSeederBuilder::create();

        $builder
            ->visible('public_key', 'public')
            ->visible('protected_key', 'protected')
            ->visible('private_key', 'private');

        $seeder = $builder->build();
        $seederVisibility = $seeder->getVisibility();

        $this->assertEquals('public', $seederVisibility['public_key']);
        $this->assertEquals('protected', $seederVisibility['protected_key']);
        $this->assertEquals('private', $seederVisibility['private_key']);
    }

    /**
     * Test public() method sets visibility to public.
     */
    public function testPublicSetsVisibilityToPublic(): void
    {
        $builder = ConfigSeederBuilder::create();
        $result = $builder->public('app_name');

        $this->assertSame($builder, $result);

        $seeder = $builder->build();
        $seederVisibility = $seeder->getVisibility();

        $this->assertEquals('public', $seederVisibility['app_name']);
    }

    /**
     * Test protected() method sets visibility to protected.
     */
    public function testProtectedSetsVisibilityToProtected(): void
    {
        $builder = ConfigSeederBuilder::create();
        $result = $builder->protected('internal_key');

        $this->assertSame($builder, $result);

        $seeder = $builder->build();
        $seederVisibility = $seeder->getVisibility();

        $this->assertEquals('protected', $seederVisibility['internal_key']);
    }

    /**
     * Test private() method sets visibility to private.
     */
    public function testPrivateSetsVisibilityToPrivate(): void
    {
        $builder = ConfigSeederBuilder::create();
        $result = $builder->private('secret_key');

        $this->assertSame($builder, $result);

        $seeder = $builder->build();
        $seederVisibility = $seeder->getVisibility();

        $this->assertEquals('private', $seederVisibility['secret_key']);
    }

    /**
     * Test build() method returns TenantConfigSeederInterface.
     */
    public function testBuildReturnsTenantConfigSeederInterface(): void
    {
        $builder = ConfigSeederBuilder::create();
        $seeder = $builder->build();

        $this->assertInstanceOf(TenantConfigSeederInterface::class, $seeder);
        $this->assertInstanceOf(GenericTenantSeeder::class, $seeder);
    }

    /**
     * Test build() method with default values.
     */
    public function testBuildWithDefaultValues(): void
    {
        $builder = ConfigSeederBuilder::create();
        $seeder = $builder->build();

        $this->assertEquals([], $seeder->getConfig('basic', []));
        $this->assertEquals([], $seeder->getVisibility());
        $this->assertEquals(50, $seeder->getPriority());
    }

    /**
     * Test build() method with all configured values.
     */
    public function testBuildWithAllConfiguredValues(): void
    {
        $builder = ConfigSeederBuilder::create();
        $config = ['app_name' => 'Test App', 'debug' => true];
        $visibility = ['app_name' => 'public', 'debug' => 'protected'];
        $priority = 25;

        $seeder = $builder
            ->config($config)
            ->visibility($visibility)
            ->priority($priority)
            ->build();

        $this->assertEquals($config, $seeder->getConfig('basic', []));
        $this->assertEquals($visibility, $seeder->getVisibility());
        $this->assertEquals($priority, $seeder->getPriority());
    }

    /**
     * Test fluent interface chaining.
     */
    public function testFluentInterfaceChaining(): void
    {
        $builder = ConfigSeederBuilder::create();

        $result = $builder
            ->set('app_name', 'Chained App')
            ->set('debug', false)
            ->public('app_name')
            ->private('debug')
            ->priority(75)
            ->config(['extra' => 'value'])
            ->visible('extra', 'protected');

        $this->assertSame($builder, $result);

        $seeder = $result->build();
        $seederConfig = $seeder->getConfig('basic', []);
        $seederVisibility = $seeder->getVisibility();

        $this->assertEquals('Chained App', $seederConfig['app_name']);
        $this->assertFalse($seederConfig['debug']);
        $this->assertEquals('value', $seederConfig['extra']);

        $this->assertEquals('public', $seederVisibility['app_name']);
        $this->assertEquals('private', $seederVisibility['debug']);
        $this->assertEquals('protected', $seederVisibility['extra']);

        $this->assertEquals(75, $seeder->getPriority());
    }

    /**
     * Test mixed usage of different configuration methods.
     */
    public function testMixedConfigurationMethods(): void
    {
        $builder = ConfigSeederBuilder::create();

        $builder
            ->config(['initial' => 'value'])
            ->set('app_name', 'Mixed App')
            ->configs([
                ['setting1' => 'value1'],
                ['setting2' => 'value2'],
            ])
            ->set('final', 'setting')
            ->visibility(['initial' => 'public'])
            ->protected('app_name')
            ->visible('setting1', 'private')
            ->public('final');

        $seeder = $builder->build();
        $seederConfig = $seeder->getConfig('basic', []);
        $seederVisibility = $seeder->getVisibility();

        $this->assertEquals('value', $seederConfig['initial']);
        $this->assertEquals('Mixed App', $seederConfig['app_name']);
        $this->assertEquals('value1', $seederConfig['setting1']);
        $this->assertEquals('value2', $seederConfig['setting2']);
        $this->assertEquals('setting', $seederConfig['final']);

        $this->assertEquals('public', $seederVisibility['initial']);
        $this->assertEquals('protected', $seederVisibility['app_name']);
        $this->assertEquals('private', $seederVisibility['setting1']);
        $this->assertEquals('public', $seederVisibility['final']);
    }

    /**
     * Test that build() can be called multiple times.
     */
    public function testBuildCanBeCalledMultipleTimes(): void
    {
        $builder = ConfigSeederBuilder::create()
            ->set('app_name', 'Test App')
            ->priority(100);

        $seeder1 = $builder->build();
        $seeder2 = $builder->build();

        $this->assertEquals($seeder1->getConfig('basic', []), $seeder2->getConfig('basic', []));
        $this->assertEquals($seeder1->getVisibility(), $seeder2->getVisibility());
        $this->assertEquals($seeder1->getPriority(), $seeder2->getPriority());
    }

    /**
     * Test that modifications after build() affect subsequent builds.
     */
    public function testModificationsAfterBuildAffectSubsequentBuilds(): void
    {
        $builder = ConfigSeederBuilder::create()->set('app_name', 'Original');
        $seeder1 = $builder->build();

        $builder->set('app_name', 'Modified');
        $seeder2 = $builder->build();

        $this->assertEquals('Original', $seeder1->getConfig('basic', [])['app_name']);
        $this->assertEquals('Modified', $seeder2->getConfig('basic', [])['app_name']);
    }

    /**
     * Test complex nested configuration values.
     */
    public function testComplexNestedConfigurationValues(): void
    {
        $builder = ConfigSeederBuilder::create();
        $complexConfig = [
            'database' => [
                'connections' => [
                    'mysql' => [
                        'host' => 'localhost',
                        'port' => 3306,
                        'credentials' => [
                            'username' => 'admin',
                            'password' => 'secret',
                        ],
                    ],
                ],
            ],
            'cache' => [
                'stores' => [
                    'redis' => [
                        'host' => 'redis.example.com',
                        'port' => 6379,
                    ],
                ],
            ],
        ];

        $seeder = $builder->config($complexConfig)->build();
        $seederConfig = $seeder->getConfig('basic', []);

        $this->assertEquals('localhost', $seederConfig['database']['connections']['mysql']['host']);
        $this->assertEquals(3306, $seederConfig['database']['connections']['mysql']['port']);
        $this->assertEquals('admin', $seederConfig['database']['connections']['mysql']['credentials']['username']);
        $this->assertEquals('redis.example.com', $seederConfig['cache']['stores']['redis']['host']);
    }

    /**
     * Test large number of configuration values.
     */
    public function testLargeNumberOfConfigurationValues(): void
    {
        $builder = ConfigSeederBuilder::create();

        // Add 100 configuration values
        for ($i = 1; $i <= 100; $i++) {
            $builder->set("key_{$i}", "value_{$i}");
            $builder->public("key_{$i}");
        }

        $seeder = $builder->build();
        $seederConfig = $seeder->getConfig('basic', []);
        $seederVisibility = $seeder->getVisibility();

        $this->assertCount(100, $seederConfig);
        $this->assertCount(100, $seederVisibility);

        // Verify some random values
        $this->assertEquals('value_25', $seederConfig['key_25']);
        $this->assertEquals('value_75', $seederConfig['key_75']);
        $this->assertEquals('public', $seederVisibility['key_50']);
    }
}
