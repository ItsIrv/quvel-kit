<?php

namespace Modules\Tenant\Tests\Unit\Builders;

use Modules\Tenant\Builders\TenantExclusionBuilder;
use Modules\Tenant\Contracts\ExclusionBuilderInterface;
use Modules\Tenant\ValueObjects\TenantExclusionConfig;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for TenantExclusionBuilder.
 *
 * @covers \Modules\Tenant\Builders\TenantExclusionBuilder
 */
class TenantExclusionBuilderTest extends TestCase
{
    /**
     * Test that create() returns a new instance.
     */
    public function test_create_returns_new_instance(): void
    {
        $builder = TenantExclusionBuilder::create();

        $this->assertInstanceOf(TenantExclusionBuilder::class, $builder);
        $this->assertInstanceOf(ExclusionBuilderInterface::class, $builder);
    }

    /**
     * Test that create() returns different instances.
     */
    public function test_create_returns_different_instances(): void
    {
        $builder1 = TenantExclusionBuilder::create();
        $builder2 = TenantExclusionBuilder::create();

        $this->assertNotSame($builder1, $builder2);
    }

    /**
     * Test paths() method with string input.
     */
    public function test_paths_with_string_input(): void
    {
        $builder = TenantExclusionBuilder::create();
        $path = '/api/health';
        $result = $builder->paths($path);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals([$path], $config->paths);
    }

    /**
     * Test paths() method with array input.
     */
    public function test_paths_with_array_input(): void
    {
        $builder = TenantExclusionBuilder::create();
        $paths = ['/api/health', '/metrics', '/status'];
        $result = $builder->paths($paths);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals($paths, $config->paths);
    }

    /**
     * Test paths() method with multiple calls merges arrays.
     */
    public function test_paths_with_multiple_calls_merges_arrays(): void
    {
        $builder = TenantExclusionBuilder::create();
        $paths1 = ['/api/health', '/metrics'];
        $paths2 = ['/status', '/ping'];

        $builder->paths($paths1)->paths($paths2);

        $config = $builder->build();
        $expected = array_merge($paths1, $paths2);
        $this->assertEquals($expected, $config->paths);
    }

    /**
     * Test paths() method removes duplicates.
     */
    public function test_paths_removes_duplicates(): void
    {
        $builder = TenantExclusionBuilder::create();
        $paths1 = ['/api/health', '/metrics'];
        $paths2 = ['/metrics', '/status']; // '/metrics' is duplicate

        $builder->paths($paths1)->paths($paths2);

        $config = $builder->build();
        $expected = ['/api/health', '/metrics', '/status'];
        $this->assertEquals($expected, array_values($config->paths));
    }

    /**
     * Test paths() method with empty array.
     */
    public function test_paths_with_empty_array(): void
    {
        $builder = TenantExclusionBuilder::create();
        $result = $builder->paths([]);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals([], $config->paths);
    }

    /**
     * Test path() method adds single path.
     */
    public function test_path_adds_single_path(): void
    {
        $builder = TenantExclusionBuilder::create();
        $path = '/admin/login';
        $result = $builder->path($path);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals([$path], $config->paths);
    }

    /**
     * Test path() method with multiple calls.
     */
    public function test_path_with_multiple_calls(): void
    {
        $builder = TenantExclusionBuilder::create();
        $path1 = '/admin/login';
        $path2 = '/public/assets';
        $path3 = '/api/status';

        $builder->path($path1)->path($path2)->path($path3);

        $config = $builder->build();
        $this->assertEquals([$path1, $path2, $path3], $config->paths);
    }

    /**
     * Test path() method removes duplicates.
     */
    public function test_path_removes_duplicates(): void
    {
        $builder = TenantExclusionBuilder::create();
        $path = '/admin/login';

        $builder->path($path)->path($path)->path($path);

        $config = $builder->build();
        $this->assertEquals([$path], $config->paths);
    }

    /**
     * Test patterns() method with string input.
     */
    public function test_patterns_with_string_input(): void
    {
        $builder = TenantExclusionBuilder::create();
        $pattern = '/api/*';
        $result = $builder->patterns($pattern);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals([$pattern], $config->patterns);
    }

    /**
     * Test patterns() method with array input.
     */
    public function test_patterns_with_array_input(): void
    {
        $builder = TenantExclusionBuilder::create();
        $patterns = ['/api/*', '/admin/*', '*.css'];
        $result = $builder->patterns($patterns);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals($patterns, $config->patterns);
    }

    /**
     * Test patterns() method with multiple calls merges arrays.
     */
    public function test_patterns_with_multiple_calls_merges_arrays(): void
    {
        $builder = TenantExclusionBuilder::create();
        $patterns1 = ['/api/*', '/admin/*'];
        $patterns2 = ['*.css', '*.js'];

        $builder->patterns($patterns1)->patterns($patterns2);

        $config = $builder->build();
        $expected = array_merge($patterns1, $patterns2);
        $this->assertEquals($expected, $config->patterns);
    }

    /**
     * Test patterns() method removes duplicates.
     */
    public function test_patterns_removes_duplicates(): void
    {
        $builder = TenantExclusionBuilder::create();
        $patterns1 = ['/api/*', '*.css'];
        $patterns2 = ['*.css', '/public/*']; // '*.css' is duplicate

        $builder->patterns($patterns1)->patterns($patterns2);

        $config = $builder->build();
        $expected = ['/api/*', '*.css', '/public/*'];
        $this->assertEquals($expected, array_values($config->patterns));
    }

    /**
     * Test patterns() method with empty array.
     */
    public function test_patterns_with_empty_array(): void
    {
        $builder = TenantExclusionBuilder::create();
        $result = $builder->patterns([]);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals([], $config->patterns);
    }

    /**
     * Test pattern() method adds single pattern.
     */
    public function test_pattern_adds_single_pattern(): void
    {
        $builder = TenantExclusionBuilder::create();
        $pattern = '/admin/*';
        $result = $builder->pattern($pattern);

        $this->assertSame($builder, $result);

        $config = $builder->build();
        $this->assertEquals([$pattern], $config->patterns);
    }

    /**
     * Test pattern() method with multiple calls.
     */
    public function test_pattern_with_multiple_calls(): void
    {
        $builder = TenantExclusionBuilder::create();
        $pattern1 = '/admin/*';
        $pattern2 = '*.js';
        $pattern3 = '/api/v*/health';

        $builder->pattern($pattern1)->pattern($pattern2)->pattern($pattern3);

        $config = $builder->build();
        $this->assertEquals([$pattern1, $pattern2, $pattern3], $config->patterns);
    }

    /**
     * Test pattern() method removes duplicates.
     */
    public function test_pattern_removes_duplicates(): void
    {
        $builder = TenantExclusionBuilder::create();
        $pattern = '/admin/*';

        $builder->pattern($pattern)->pattern($pattern)->pattern($pattern);

        $config = $builder->build();
        $this->assertEquals([$pattern], $config->patterns);
    }

    /**
     * Test build() method returns TenantExclusionConfig with default values.
     */
    public function test_build_returns_config_with_defaults(): void
    {
        $builder = TenantExclusionBuilder::create();
        $config = $builder->build();

        $this->assertInstanceOf(TenantExclusionConfig::class, $config);
        $this->assertEquals([], $config->paths);
        $this->assertEquals([], $config->patterns);
    }

    /**
     * Test build() method returns TenantExclusionConfig with all configured values.
     */
    public function test_build_returns_config_with_all_values(): void
    {
        $builder = TenantExclusionBuilder::create();
        $paths = ['/admin/login', '/api/health'];
        $patterns = ['/public/*', '*.css'];

        $config = $builder
            ->paths($paths)
            ->patterns($patterns)
            ->build();

        $this->assertInstanceOf(TenantExclusionConfig::class, $config);
        $this->assertEquals($paths, $config->paths);
        $this->assertEquals($patterns, $config->patterns);
    }

    /**
     * Test fluent interface chaining.
     */
    public function test_fluent_interface_chaining(): void
    {
        $builder = TenantExclusionBuilder::create();

        $result = $builder
            ->path('/admin/login')
            ->paths(['/api/health', '/metrics'])
            ->pattern('/public/*')
            ->patterns(['*.css', '*.js']);

        $this->assertSame($builder, $result);

        $config = $result->build();
        $expectedPaths = ['/admin/login', '/api/health', '/metrics'];
        $expectedPatterns = ['/public/*', '*.css', '*.js'];

        $this->assertEquals($expectedPaths, $config->paths);
        $this->assertEquals($expectedPatterns, $config->patterns);
    }

    /**
     * Test mixed usage of individual and bulk methods.
     */
    public function test_mixed_individual_and_bulk_methods(): void
    {
        $builder = TenantExclusionBuilder::create();

        $builder
            ->path('/admin')
            ->paths(['/api/v1', '/api/v2'])
            ->path('/health')
            ->pattern('*.css')
            ->patterns(['/static/*', '/assets/*'])
            ->pattern('*.js');

        $config = $builder->build();

        $expectedPaths = ['/admin', '/api/v1', '/api/v2', '/health'];
        $expectedPatterns = ['*.css', '/static/*', '/assets/*', '*.js'];

        $this->assertEquals($expectedPaths, $config->paths);
        $this->assertEquals($expectedPatterns, $config->patterns);
    }

    /**
     * Test that build() can be called multiple times.
     */
    public function test_build_can_be_called_multiple_times(): void
    {
        $builder = TenantExclusionBuilder::create()
            ->paths(['/admin', '/api'])
            ->patterns(['*.css', '*.js']);

        $config1 = $builder->build();
        $config2 = $builder->build();

        $this->assertEquals($config1->paths, $config2->paths);
        $this->assertEquals($config1->patterns, $config2->patterns);
    }

    /**
     * Test that modifications after build() affect subsequent builds.
     */
    public function test_modifications_after_build_affect_subsequent_builds(): void
    {
        $builder = TenantExclusionBuilder::create()->path('/admin');
        $config1 = $builder->build();

        $builder->path('/api');
        $config2 = $builder->build();

        $this->assertEquals(['/admin'], $config1->paths);
        $this->assertEquals(['/admin', '/api'], $config2->paths);
    }

    /**
     * Test complex wildcard patterns.
     */
    public function test_complex_wildcard_patterns(): void
    {
        $builder = TenantExclusionBuilder::create();
        $complexPatterns = [
            '/api/v*/health',
            '/admin/*/settings',
            '*.{css,js,png}',
            '/storage/*/temp/*',
            '[a-z]*/uploads',
        ];

        $config = $builder->patterns($complexPatterns)->build();

        $this->assertEquals($complexPatterns, $config->patterns);
    }

    /**
     * Test very long paths and patterns.
     */
    public function test_very_long_paths_and_patterns(): void
    {
        $builder = TenantExclusionBuilder::create();
        $longPath = '/very/long/path/that/goes/very/deep/in/the/directory/structure/and/keeps/going';
        $longPattern = '/api/v*/some/really/long/endpoint/path/that/might/exist/in/real/world/*';

        $config = $builder->path($longPath)->pattern($longPattern)->build();

        $this->assertEquals([$longPath], $config->paths);
        $this->assertEquals([$longPattern], $config->patterns);
    }

    /**
     * Test special characters in paths and patterns.
     */
    public function test_special_characters_in_paths_and_patterns(): void
    {
        $builder = TenantExclusionBuilder::create();
        $specialPaths = [
            '/api/users/{id}',
            '/search?q=*',
            '/admin/users/{id}/edit',
        ];
        $specialPatterns = [
            '/api/users/[0-9]+',
            '/files/*.{jpg,png,gif}',
            '/admin/*/*/edit',
        ];

        $config = $builder->paths($specialPaths)->patterns($specialPatterns)->build();

        $this->assertEquals($specialPaths, $config->paths);
        $this->assertEquals($specialPatterns, $config->patterns);
    }
}