<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Modules\Tenant\Services\TenantConfigSeederRegistry;
use Modules\Tenant\Services\TenantModuleConfigLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantConfigSeederRegistry::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
class TenantConfigSeederRegistryTest extends TestCase
{
    private TenantConfigSeederRegistry $registry;
    private TenantModuleConfigLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loader   = $this->createMock(TenantModuleConfigLoader::class);
        $this->registry = new TenantConfigSeederRegistry($this->loader);
    }

    /**
     * Test getSeedConfig returns empty array when no seeders exist.
     */
    public function testGetSeedConfigReturnsEmptyArrayWhenNoSeedersExist(): void
    {
        $this->loader->expects($this->once())
            ->method('getSeedersForTemplate')
            ->with('basic')
            ->willReturn([]);

        $result = $this->registry->getSeedConfig('basic');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getSeedConfig with base config merges correctly.
     */
    public function testGetSeedConfigWithBaseConfigMergesCorrectly(): void
    {
        $this->loader->expects($this->once())
            ->method('getSeedersForTemplate')
            ->with('premium')
            ->willReturn([]);

        $baseConfig = ['existing_key' => 'existing_value'];
        $result     = $this->registry->getSeedConfig('premium', $baseConfig);

        $this->assertEquals($baseConfig, $result);
    }

    /**
     * Test getSeedConfig with array config data merges correctly.
     */
    public function testGetSeedConfigWithArrayConfigDataMergesCorrectly(): void
    {
        $seeders = [
            [
                'config'   => ['key1' => 'value1', 'key2' => 'value2'],
                'priority' => 10,
            ],
            [
                'config'   => ['key3' => 'value3', 'key1' => 'overridden'],
                'priority' => 20,
            ],
        ];

        $this->loader->expects($this->once())
            ->method('getSeedersForTemplate')
            ->with('enterprise')
            ->willReturn($seeders);

        $result = $this->registry->getSeedConfig('enterprise');

        $expected = [
            'key1' => 'overridden', // Later seeder overwrites
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getSeedConfig with callable config executes callback.
     */
    public function testGetSeedConfigWithCallableConfigExecutesCallback(): void
    {
        $callback = function (string $template, array $config): array {
            $this->assertEquals('test_template', $template);
            $this->assertEquals(['base_key' => 'base_value'], $config);

            return ['callback_key' => 'callback_value'];
        };

        $seeders = [
            [
                'config'   => $callback,
                'priority' => 10,
            ],
        ];

        $this->loader->expects($this->once())
            ->method('getSeedersForTemplate')
            ->with('test_template')
            ->willReturn($seeders);

        $baseConfig = ['base_key' => 'base_value'];
        $result     = $this->registry->getSeedConfig('test_template', $baseConfig);

        $expected = [
            'base_key'     => 'base_value',
            'callback_key' => 'callback_value',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getSeedConfig with non-array callback result ignores result.
     */
    public function testGetSeedConfigWithNonArrayCallbackResultIgnoresResult(): void
    {
        $callback = function (string $template, array $config) {
            return 'not_an_array'; // Should be ignored
        };

        $seeders = [
            [
                'config'   => $callback,
                'priority' => 10,
            ],
            [
                'config'   => ['valid_key' => 'valid_value'],
                'priority' => 20,
            ],
        ];

        $this->loader->expects($this->once())
            ->method('getSeedersForTemplate')
            ->with('test')
            ->willReturn($seeders);

        $result = $this->registry->getSeedConfig('test');

        $this->assertEquals(['valid_key' => 'valid_value'], $result);
    }

    /**
     * Test getSeedConfig caches results for same template.
     */
    public function testGetSeedConfigCachesResultsForSameTemplate(): void
    {
        $seeders = [
            ['config' => ['cached_key' => 'cached_value']],
        ];

        // Should only be called once despite multiple calls
        $this->loader->expects($this->once())
            ->method('getSeedersForTemplate')
            ->with('cached_template')
            ->willReturn($seeders);

        // First call
        $result1 = $this->registry->getSeedConfig('cached_template');

        // Second call should use cache
        $result2 = $this->registry->getSeedConfig('cached_template');

        $this->assertEquals($result1, $result2);
        $this->assertEquals(['cached_key' => 'cached_value'], $result1);
    }

    /**
     * Test getSeedConfig handles mixed config types correctly.
     */
    public function testGetSeedConfigHandlesMixedConfigTypesCorrectly(): void
    {
        $seeders = [
            [
                'config'   => ['array_key' => 'array_value'],
                'priority' => 10,
            ],
            [
                'config'   => function (string $template, array $config): array {
                    return ['callback_key' => 'callback_value'];
                },
                'priority' => 20,
            ],
            [
                'config'   => 'invalid_string', // Should be ignored
                'priority' => 30,
            ],
        ];

        $this->loader->expects($this->once())
            ->method('getSeedersForTemplate')
            ->with('mixed')
            ->willReturn($seeders);

        $result = $this->registry->getSeedConfig('mixed');

        $expected = [
            'array_key'    => 'array_value',
            'callback_key' => 'callback_value',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getSeedVisibility returns empty array when no visibility exists.
     */
    public function testGetSeedVisibilityReturnsEmptyArrayWhenNoVisibilityExists(): void
    {
        $this->loader->expects($this->once())
            ->method('loadAllModuleConfigs')
            ->willReturn([]);

        $result = $this->registry->getSeedVisibility('basic');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test getSeedVisibility with base visibility merges correctly.
     */
    public function testGetSeedVisibilityWithBaseVisibilityMergesCorrectly(): void
    {
        $this->loader->expects($this->once())
            ->method('loadAllModuleConfigs')
            ->willReturn([]);

        $baseVisibility = ['existing_key' => 'public'];
        $result         = $this->registry->getSeedVisibility('premium', $baseVisibility);

        $this->assertEquals($baseVisibility, $result);
    }

    /**
     * Test getSeedVisibility loads template-specific visibility.
     */
    public function testGetSeedVisibilityLoadsTemplateSpecificVisibility(): void
    {
        $moduleConfigs = [
            'CoreModule' => [
                'seeders' => [
                    'basic'   => [
                        'visibility' => ['app_name' => 'public', 'app_url' => 'protected'],
                    ],
                    'premium' => [
                        'visibility' => ['premium_key' => 'private'],
                    ],
                ],
            ],
            'AuthModule' => [
                'seeders' => [
                    'basic' => [
                        'visibility' => ['auth_key' => 'public'],
                    ],
                ],
            ],
        ];

        $this->loader->expects($this->once())
            ->method('loadAllModuleConfigs')
            ->willReturn($moduleConfigs);

        $result = $this->registry->getSeedVisibility('basic');

        $expected = [
            'app_name' => 'public',
            'app_url'  => 'protected',
            'auth_key' => 'public',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getSeedVisibility loads shared seeder visibility.
     */
    public function testGetSeedVisibilityLoadsSharedSeederVisibility(): void
    {
        $moduleConfigs = [
            'CoreModule' => [
                'shared_seeders' => [
                    [
                        'visibility' => ['shared_key1' => 'public'],
                    ],
                    [
                        'visibility' => ['shared_key2' => 'private'],
                    ],
                ],
            ],
            'AuthModule' => [
                'shared_seeders' => [
                    [
                        'visibility' => ['auth_shared' => 'protected'],
                    ],
                ],
            ],
        ];

        $this->loader->expects($this->once())
            ->method('loadAllModuleConfigs')
            ->willReturn($moduleConfigs);

        $result = $this->registry->getSeedVisibility('any_template');

        $expected = [
            'shared_key1' => 'public',
            'shared_key2' => 'private',
            'auth_shared' => 'protected',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getSeedVisibility combines template-specific and shared visibility.
     */
    public function testGetSeedVisibilityCombinesTemplateSpecificAndSharedVisibility(): void
    {
        $moduleConfigs = [
            'CoreModule' => [
                'seeders'        => [
                    'enterprise' => [
                        'visibility' => ['template_key' => 'public'],
                    ],
                ],
                'shared_seeders' => [
                    [
                        'visibility' => ['shared_key' => 'private'],
                    ],
                ],
            ],
        ];

        $this->loader->expects($this->once())
            ->method('loadAllModuleConfigs')
            ->willReturn($moduleConfigs);

        $result = $this->registry->getSeedVisibility('enterprise');

        $expected = [
            'template_key' => 'public',
            'shared_key'   => 'private',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * Test getSeedVisibility ignores non-array visibility configurations.
     */
    public function testGetSeedVisibilityIgnoresNonArrayVisibilityConfigurations(): void
    {
        $moduleConfigs = [
            'CoreModule' => [
                'seeders'        => [
                    'basic' => [
                        'visibility' => 'invalid_string' // Should be ignored
                    ],
                ],
                'shared_seeders' => [
                    [
                        'visibility' => ['valid_key' => 'public'],
                    ],
                    [
                        'visibility' => null // Should be ignored
                    ],
                ],
            ],
        ];

        $this->loader->expects($this->once())
            ->method('loadAllModuleConfigs')
            ->willReturn($moduleConfigs);

        $result = $this->registry->getSeedVisibility('basic');

        $this->assertEquals(['valid_key' => 'public'], $result);
    }

    /**
     * Test getSeedVisibility caches results for same template.
     */
    public function testGetSeedVisibilityCachesResultsForSameTemplate(): void
    {
        $moduleConfigs = [
            'CoreModule' => [
                'seeders' => [
                    'cached' => [
                        'visibility' => ['cached_key' => 'public'],
                    ],
                ],
            ],
        ];

        // Should only be called once despite multiple calls
        $this->loader->expects($this->once())
            ->method('loadAllModuleConfigs')
            ->willReturn($moduleConfigs);

        // First call
        $result1 = $this->registry->getSeedVisibility('cached');

        // Second call should use cache
        $result2 = $this->registry->getSeedVisibility('cached');

        $this->assertEquals($result1, $result2);
        $this->assertEquals(['cached_key' => 'public'], $result1);
    }

    /**
     * Test getSeedVisibility handles modules without seeder configs.
     */
    public function testGetSeedVisibilityHandlesModulesWithoutSeederConfigs(): void
    {
        $moduleConfigs = [
            'ModuleWithoutSeeders' => [
                'pipes' => ['SomePipe::class'],
            ],
            'ModuleWithSeeders'    => [
                'seeders' => [
                    'basic' => [
                        'visibility' => ['valid_key' => 'public'],
                    ],
                ],
            ],
        ];

        $this->loader->expects($this->once())
            ->method('loadAllModuleConfigs')
            ->willReturn($moduleConfigs);

        $result = $this->registry->getSeedVisibility('basic');

        $this->assertEquals(['valid_key' => 'public'], $result);
    }

    /**
     * Test getSeedVisibility handles missing shared_seeders gracefully.
     */
    public function testGetSeedVisibilityHandlesMissingSharedSeedersGracefully(): void
    {
        $moduleConfigs = [
            'CoreModule' => [
                'seeders' => [
                    'basic' => [
                        'visibility' => ['template_key' => 'public'],
                    ],
                ],
                // No shared_seeders key
            ],
            'AuthModule' => [
                'shared_seeders' => 'invalid_not_array' // Invalid shared_seeders
            ],
        ];

        $this->loader->expects($this->once())
            ->method('loadAllModuleConfigs')
            ->willReturn($moduleConfigs);

        $result = $this->registry->getSeedVisibility('basic');

        $this->assertEquals(['template_key' => 'public'], $result);
    }

    /**
     * Test that different templates maintain separate caches.
     */
    public function testDifferentTemplatesMaintainSeparateCaches(): void
    {
        // Setup different seeders for different templates
        $this->loader->expects($this->exactly(2))
            ->method('getSeedersForTemplate')
            ->willReturnCallback(function (string $template) {
                if ($template === 'template1') {
                    return [['config' => ['key1' => 'value1']]];
                }
                if ($template === 'template2') {
                    return [['config' => ['key2' => 'value2']]];
                }
                return [];
            });

        $result1 = $this->registry->getSeedConfig('template1');
        $result2 = $this->registry->getSeedConfig('template2');

        $this->assertEquals(['key1' => 'value1'], $result1);
        $this->assertEquals(['key2' => 'value2'], $result2);
    }

    /**
     * Test that different templates maintain separate visibility caches.
     */
    public function testDifferentTemplatesMaintainSeparateVisibilityCaches(): void
    {
        $moduleConfigs = [
            'CoreModule' => [
                'seeders' => [
                    'template1' => [
                        'visibility' => ['key1' => 'public'],
                    ],
                    'template2' => [
                        'visibility' => ['key2' => 'private'],
                    ],
                ],
            ],
        ];

        // Should be called twice for different templates
        $this->loader->expects($this->exactly(2))
            ->method('loadAllModuleConfigs')
            ->willReturn($moduleConfigs);

        $result1 = $this->registry->getSeedVisibility('template1');
        $result2 = $this->registry->getSeedVisibility('template2');

        $this->assertEquals(['key1' => 'public'], $result1);
        $this->assertEquals(['key2' => 'private'], $result2);
    }
}
