<?php

namespace Modules\Tenant\Tests\Unit\Builders;

use Modules\Tenant\Builders\TenantExclusionBuilder;
use Modules\Tenant\Contracts\ExclusionBuilderInterface;
use Modules\Tenant\ValueObjects\TenantExclusionConfig;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(TenantExclusionBuilder::class)]
#[Group('tenant-module')]
#[Group('tenant-builders')]
class TenantExclusionBuilderTest extends TestCase
{
    /**
     * Test that create() returns a new instance.
     */
    public function testCreateReturnsNewInstance(): void
    {
        $builder = TenantExclusionBuilder::create();

        $this->assertInstanceOf(TenantExclusionBuilder::class, $builder);
        $this->assertInstanceOf(ExclusionBuilderInterface::class, $builder);
    }

    /**
     * Test that create() returns different instances.
     */
    public function testCreateReturnsDifferentInstances(): void
    {
        $builder1 = TenantExclusionBuilder::create();
        $builder2 = TenantExclusionBuilder::create();

        $this->assertNotSame($builder1, $builder2);
    }

    /**
     * Test paths() method with string input.
     */
    public function testPathsWithStringInput(): void
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
    public function testPathsWithArrayInput(): void
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
    public function testPathsWithMultipleCallsMergesArrays(): void
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
    public function testPathsRemovesDuplicates(): void
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
    public function testPathsWithEmptyArray(): void
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
    public function testPathAddsSinglePath(): void
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
    public function testPathWithMultipleCalls(): void
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
    public function testPathRemovesDuplicates(): void
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
    public function testPatternsWithStringInput(): void
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
    public function testPatternsWithArrayInput(): void
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
    public function testPatternsWithMultipleCallsMergesArrays(): void
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
    public function testPatternsRemovesDuplicates(): void
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
    public function testPatternsWithEmptyArray(): void
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
    public function testPatternAddsSinglePattern(): void
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
    public function testPatternWithMultipleCalls(): void
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
    public function testPatternRemovesDuplicates(): void
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
    public function testBuildReturnsConfigWithDefaults(): void
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
    public function testBuildReturnsConfigWithAllValues(): void
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
    public function testFluentInterfaceChaining(): void
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
    public function testMixedIndividualAndBulkMethods(): void
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
    public function testBuildCanBeCalledMultipleTimes(): void
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
    public function testModificationsAfterBuildAffectSubsequentBuilds(): void
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
    public function testComplexWildcardPatterns(): void
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
    public function testVeryLongPathsAndPatterns(): void
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
    public function testSpecialCharactersInPathsAndPatterns(): void
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
