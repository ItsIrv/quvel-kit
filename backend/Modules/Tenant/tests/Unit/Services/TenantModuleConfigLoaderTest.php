<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Modules\Tenant\Services\TenantModuleConfigLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(TenantModuleConfigLoader::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
final class TenantModuleConfigLoaderTest extends TestCase
{
    private TenantModuleConfigLoader $loader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loader = new TenantModuleConfigLoader();
    }

    #[TestDox('Should get seeders for specific template with priority sorting')]
    public function testGetSeedersForTemplate(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'seeders' => [
                    'basic' => ['config' => ['key1' => 'value1'], 'priority' => 30],
                    'isolated' => ['config' => ['key2' => 'value2'], 'priority' => 10],
                ],
                'shared_seeders' => [
                    ['config' => ['shared1' => 'shared_value1'], 'priority' => 20],
                    ['config' => ['shared2' => 'shared_value2']], // No priority (defaults to 50)
                ],
            ],
            'Module2' => [
                'seeders' => [
                    'basic' => ['config' => ['key3' => 'value3'], 'priority' => 5],
                ],
                'shared_seeders' => [
                    ['config' => ['shared3' => 'shared_value3'], 'priority' => 15],
                ],
            ],
        ]);

        $basicSeeders = $this->loader->getSeedersForTemplate('basic');

        // Should include template-specific seeders and shared seeders, sorted by priority
        $this->assertCount(5, $basicSeeders);

        // Check priority order: 5, 15, 20, 30, 50
        // Note: array_column returns null for missing keys, so we need to use the default
        $priorities = array_map(fn($seeder) => $seeder['priority'] ?? 50, $basicSeeders);
        $this->assertEquals([5, 15, 20, 30, 50], $priorities);

        $this->assertEquals('value3', $basicSeeders[0]['config']['key3']);
        $this->assertEquals('shared_value3', $basicSeeders[1]['config']['shared3']);
        $this->assertEquals('shared_value1', $basicSeeders[2]['config']['shared1']);
        $this->assertEquals('value1', $basicSeeders[3]['config']['key1']);
        $this->assertEquals('shared_value2', $basicSeeders[4]['config']['shared2']);
    }

    #[TestDox('Should get seeders for template that does not exist')]
    public function testGetSeedersForNonExistentTemplate(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'seeders' => [
                    'basic' => ['config' => ['key1' => 'value1']],
                ],
                'shared_seeders' => [
                    ['config' => ['shared1' => 'shared_value1'], 'priority' => 10],
                ],
            ],
        ]);

        $seeders = $this->loader->getSeedersForTemplate('nonexistent');

        // Should only return shared seeders
        $this->assertCount(1, $seeders);
        $this->assertEquals('shared_value1', $seeders[0]['config']['shared1']);
    }

    #[TestDox('Should handle modules with invalid shared seeders')]
    public function testGetSeedersWithInvalidSharedSeeders(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'seeders' => [
                    'basic' => ['config' => ['key1' => 'value1']],
                ],
                'shared_seeders' => 'invalid_not_array',
            ],
        ]);

        $seeders = $this->loader->getSeedersForTemplate('basic');

        // Should only include template-specific seeders, ignore invalid shared_seeders
        $this->assertCount(1, $seeders);
        $this->assertEquals('value1', $seeders[0]['config']['key1']);
    }

    #[TestDox('Should handle seeders with missing priority')]
    public function testGetSeedersWithMissingPriority(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'seeders' => [
                    'basic' => ['config' => ['key1' => 'value1']], // No priority
                ],
            ],
        ]);

        $seeders = $this->loader->getSeedersForTemplate('basic');

        $this->assertCount(1, $seeders);
        $this->assertEquals(50, $seeders[0]['priority'] ?? 50); // Default priority
    }

    #[TestDox('Should get all pipes from modules')]
    public function testGetAllPipes(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'pipes' => ['Pipe1', 'Pipe2'],
            ],
            'Module2' => [
                'pipes' => ['Pipe3', 'Pipe4'],
            ],
            'Module3' => [
                // No pipes
            ],
            'Module4' => [
                'pipes' => 'invalid_not_array',
            ],
        ]);

        $pipes = $this->loader->getAllPipes();

        $this->assertCount(4, $pipes);
        $this->assertContains('Pipe1', $pipes);
        $this->assertContains('Pipe2', $pipes);
        $this->assertContains('Pipe3', $pipes);
        $this->assertContains('Pipe4', $pipes);
    }

    #[TestDox('Should get all tables from modules')]
    public function testGetAllTables(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'tables' => ['table1', 'table2'],
            ],
            'Module2' => [
                'tables' => ['table3'],
            ],
            'Module3' => [
                // No tables
            ],
            'Module4' => [
                'tables' => 'invalid_not_array',
            ],
        ]);

        $tables = $this->loader->getAllTables();

        $this->assertCount(3, $tables);
        $this->assertContains('table1', $tables);
        $this->assertContains('table2', $tables);
        $this->assertContains('table3', $tables);
    }

    #[TestDox('Should get all exclusion paths from modules')]
    public function testGetAllExclusionPaths(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'exclusions' => [
                    'paths' => ['/path1', '/path2'],
                ],
            ],
            'Module2' => [
                'exclusions' => [
                    'paths' => ['/path3'],
                ],
            ],
            'Module3' => [
                'exclusions' => [
                    'patterns' => ['*.log'], // Has patterns but no paths
                ],
            ],
            'Module4' => [
                'exclusions' => [
                    'paths' => 'invalid_not_array',
                ],
            ],
        ]);

        $paths = $this->loader->getAllExclusionPaths();

        $this->assertCount(3, $paths);
        $this->assertContains('/path1', $paths);
        $this->assertContains('/path2', $paths);
        $this->assertContains('/path3', $paths);
    }

    #[TestDox('Should get all exclusion patterns from modules')]
    public function testGetAllExclusionPatterns(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'exclusions' => [
                    'patterns' => ['*.log', '*.tmp'],
                ],
            ],
            'Module2' => [
                'exclusions' => [
                    'patterns' => ['*.cache'],
                ],
            ],
            'Module3' => [
                'exclusions' => [
                    'paths' => ['/path1'], // Has paths but no patterns
                ],
            ],
            'Module4' => [
                'exclusions' => [
                    'patterns' => 'invalid_not_array',
                ],
            ],
        ]);

        $patterns = $this->loader->getAllExclusionPatterns();

        $this->assertCount(3, $patterns);
        $this->assertContains('*.log', $patterns);
        $this->assertContains('*.tmp', $patterns);
        $this->assertContains('*.cache', $patterns);
    }

    #[TestDox('Should get visibility for specific template')]
    public function testGetVisibilityForTemplate(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'seeders' => [
                    'basic' => [
                        'visibility' => ['key1' => 'public', 'key2' => 'private'],
                    ],
                    'isolated' => [
                        'visibility' => ['key3' => 'internal'],
                    ],
                ],
                'shared_seeders' => [
                    [
                        'visibility' => ['shared1' => 'public'],
                    ],
                    [
                        'visibility' => ['shared2' => 'private'],
                    ],
                ],
            ],
            'Module2' => [
                'seeders' => [
                    'basic' => [
                        'visibility' => ['key4' => 'public'],
                    ],
                ],
                'shared_seeders' => [
                    [
                        'visibility' => ['shared3' => 'internal'],
                    ],
                ],
            ],
        ]);

        $visibility = $this->loader->getVisibilityForTemplate('basic');

        $this->assertCount(6, $visibility);
        $this->assertEquals('public', $visibility['key1']);
        $this->assertEquals('private', $visibility['key2']);
        $this->assertEquals('public', $visibility['key4']);
        $this->assertEquals('public', $visibility['shared1']);
        $this->assertEquals('private', $visibility['shared2']);
        $this->assertEquals('internal', $visibility['shared3']);
    }

    #[TestDox('Should handle visibility for template with invalid configurations')]
    public function testGetVisibilityForTemplateWithInvalidConfigurations(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'seeders' => [
                    'basic' => [
                        'visibility' => 'invalid_not_array',
                    ],
                ],
                'shared_seeders' => [
                    [
                        'visibility' => ['valid' => 'public'],
                    ],
                    [
                        'visibility' => 'invalid_not_array',
                    ],
                    [
                        // No visibility key
                    ],
                ],
            ],
            'Module2' => [
                'shared_seeders' => 'invalid_not_array',
            ],
        ]);

        $visibility = $this->loader->getVisibilityForTemplate('basic');

        // Should only include valid visibility configurations
        $this->assertCount(1, $visibility);
        $this->assertEquals('public', $visibility['valid']);
    }

    #[TestDox('Should return empty arrays when no configurations exist')]
    public function testEmptyConfigurationsWhenNoModules(): void
    {
        $this->setupLoaderWithConfigs([]);

        $this->assertEmpty($this->loader->getSeedersForTemplate('basic'));
        $this->assertEmpty($this->loader->getAllPipes());
        $this->assertEmpty($this->loader->getAllTables());
        $this->assertEmpty($this->loader->getAllExclusionPaths());
        $this->assertEmpty($this->loader->getAllExclusionPatterns());
        $this->assertEmpty($this->loader->getVisibilityForTemplate('basic'));
    }

    #[TestDox('Should handle modules with empty configuration files')]
    public function testModulesWithEmptyConfigurations(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [],
            'Module2' => [
                'seeders' => [],
                'pipes' => [],
                'tables' => [],
                'exclusions' => [],
            ],
        ]);

        $this->assertEmpty($this->loader->getSeedersForTemplate('basic'));
        $this->assertEmpty($this->loader->getAllPipes());
        $this->assertEmpty($this->loader->getAllTables());
        $this->assertEmpty($this->loader->getAllExclusionPaths());
        $this->assertEmpty($this->loader->getAllExclusionPatterns());
        $this->assertEmpty($this->loader->getVisibilityForTemplate('basic'));
    }

    #[TestDox('Should handle exclusions without paths or patterns')]
    public function testExclusionsWithoutPathsOrPatterns(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'exclusions' => [], // Empty exclusions
            ],
            'Module2' => [
                'exclusions' => [
                    'other_config' => 'value', // Has exclusions but no paths/patterns
                ],
            ],
        ]);

        $this->assertEmpty($this->loader->getAllExclusionPaths());
        $this->assertEmpty($this->loader->getAllExclusionPatterns());
    }

    #[TestDox('Should handle shared seeders without visibility')]
    public function testSharedSeedersWithoutVisibility(): void
    {
        $this->setupLoaderWithConfigs([
            'Module1' => [
                'shared_seeders' => [
                    [
                        'config' => ['key1' => 'value1'],
                        // No visibility
                    ],
                    [
                        'config' => ['key2' => 'value2'],
                        'visibility' => ['key2' => 'public'],
                    ],
                ],
            ],
        ]);

        $visibility = $this->loader->getVisibilityForTemplate('basic');

        // Should only include visibility from seeders that have it
        $this->assertCount(1, $visibility);
        $this->assertEquals('public', $visibility['key2']);
    }

    #[TestDox('Should cache loaded configurations')]
    public function testCachesLoadedConfigurations(): void
    {
        // Setup initial config
        $this->setupLoaderWithConfigs([
            'Module1' => ['pipes' => ['Pipe1']],
        ]);

        // First call should load configs
        $pipes1 = $this->loader->getAllPipes();
        $this->assertCount(1, $pipes1);

        // Modify the cached configs to test that it's using cache
        $reflection = new \ReflectionClass($this->loader);
        $property = $reflection->getProperty('loadedConfigs');
        $property->setAccessible(true);
        $property->setValue($this->loader, [
            'Module1' => ['pipes' => ['CachedPipe']],
        ]);

        // Second call should use cached value
        $pipes2 = $this->loader->getAllPipes();
        $this->assertContains('CachedPipe', $pipes2);
        $this->assertNotContains('Pipe1', $pipes2);
    }

    #[TestDox('Should return cached configs from loadAllModuleConfigs')]
    public function testLoadAllModuleConfigsReturnsCachedConfigs(): void
    {
        $configs = [
            'Module1' => ['pipes' => ['Pipe1']],
            'Module2' => ['tables' => ['table1']],
        ];

        $this->setupLoaderWithConfigs($configs);

        $result = $this->loader->loadAllModuleConfigs();

        $this->assertEquals($configs, $result);

        // Should return same reference when called again
        $result2 = $this->loader->loadAllModuleConfigs();
        $this->assertSame($result, $result2);
    }

    /**
     * Helper method to setup loader with predefined configs using reflection
     */
    private function setupLoaderWithConfigs(array $configs): void
    {
        $reflection = new \ReflectionClass($this->loader);
        $property = $reflection->getProperty('loadedConfigs');
        $property->setAccessible(true);
        $property->setValue($this->loader, $configs);
    }
}