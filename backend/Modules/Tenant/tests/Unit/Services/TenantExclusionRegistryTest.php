<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Modules\Tenant\Services\TenantExclusionRegistry;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;

class TenantExclusionRegistryTest extends TestCase
{
    private TenantExclusionRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = new TenantExclusionRegistry();
    }

    #[TestDox('Should add single excluded path')]
    public function testAddSingleExcludedPath(): void
    {
        $this->registry->excludePaths('/api/public');

        $this->assertCount(1, $this->registry->getExcludedPaths());
        $this->assertContains('/api/public', $this->registry->getExcludedPaths());
    }

    #[TestDox('Should add multiple excluded paths')]
    public function testAddMultipleExcludedPaths(): void
    {
        $paths = ['/api/public', '/api/health', '/api/status'];
        $this->registry->excludePaths($paths);

        $this->assertCount(3, $this->registry->getExcludedPaths());
        $this->assertEquals($paths, $this->registry->getExcludedPaths());
    }

    #[TestDox('Should add paths incrementally')]
    public function testAddPathsIncrementally(): void
    {
        $this->registry->excludePaths('/api/public');
        $this->registry->excludePaths(['/api/health', '/api/status']);
        $this->registry->excludePaths('/api/metrics');

        $expected = ['/api/public', '/api/health', '/api/status', '/api/metrics'];
        $this->assertCount(4, $this->registry->getExcludedPaths());
        $this->assertEquals($expected, $this->registry->getExcludedPaths());
    }

    #[TestDox('Should deduplicate excluded paths')]
    public function testDeduplicateExcludedPaths(): void
    {
        $this->registry->excludePaths('/api/public');
        $this->registry->excludePaths(['/api/health', '/api/public']);
        $this->registry->excludePaths('/api/public');

        $expected = ['/api/public', '/api/health'];
        $this->assertCount(2, $this->registry->getExcludedPaths());
        $this->assertEquals($expected, $this->registry->getExcludedPaths());
    }

    #[TestDox('Should add single excluded pattern')]
    public function testAddSingleExcludedPattern(): void
    {
        $this->registry->excludePatterns('/api/*');

        $this->assertCount(1, $this->registry->getExcludedPatterns());
        $this->assertContains('/api/*', $this->registry->getExcludedPatterns());
    }

    #[TestDox('Should add multiple excluded patterns')]
    public function testAddMultipleExcludedPatterns(): void
    {
        $patterns = ['/api/*', '*/health', '*/metrics'];
        $this->registry->excludePatterns($patterns);

        $this->assertCount(3, $this->registry->getExcludedPatterns());
        $this->assertEquals($patterns, $this->registry->getExcludedPatterns());
    }

    #[TestDox('Should add patterns incrementally')]
    public function testAddPatternsIncrementally(): void
    {
        $this->registry->excludePatterns('/api/*');
        $this->registry->excludePatterns(['*/health', '*/metrics']);
        $this->registry->excludePatterns('*/status');

        $expected = ['/api/*', '*/health', '*/metrics', '*/status'];
        $this->assertCount(4, $this->registry->getExcludedPatterns());
        $this->assertEquals($expected, $this->registry->getExcludedPatterns());
    }

    #[TestDox('Should deduplicate excluded patterns')]
    public function testDeduplicateExcludedPatterns(): void
    {
        $this->registry->excludePatterns('/api/*');
        $this->registry->excludePatterns(['*/health', '/api/*']);
        $this->registry->excludePatterns('/api/*');

        $expected = ['/api/*', '*/health'];
        $this->assertCount(2, $this->registry->getExcludedPatterns());
        $this->assertEquals($expected, $this->registry->getExcludedPatterns());
    }

    #[TestDox('Should identify exact path matches as excluded')]
    public function testIsExcludedExactMatch(): void
    {
        $this->registry->excludePaths(['/api/public', '/api/health']);

        $this->assertTrue($this->registry->isExcluded('/api/public'));
        $this->assertTrue($this->registry->isExcluded('/api/health'));
        $this->assertFalse($this->registry->isExcluded('/api/status'));
    }

    #[TestDox('Should identify pattern matches as excluded')]
    public function testIsExcludedPatternMatch(): void
    {
        $this->registry->excludePatterns(['/api/*', '*/health']);

        $this->assertTrue($this->registry->isExcluded('/api/anything'));
        $this->assertTrue($this->registry->isExcluded('/api/status'));
        $this->assertTrue($this->registry->isExcluded('/service/health'));
        $this->assertFalse($this->registry->isExcluded('/service/status'));
    }

    #[TestDox('Should handle combined exact and pattern exclusions')]
    public function testIsExcludedCombined(): void
    {
        $this->registry->excludePaths('/exact/path');
        $this->registry->excludePatterns('/api/*');

        $this->assertTrue($this->registry->isExcluded('/exact/path'));
        $this->assertTrue($this->registry->isExcluded('/api/anything'));
        $this->assertFalse($this->registry->isExcluded('/service/status'));
    }

    #[TestDox('Should handle complex pattern matching')]
    public function testComplexPatternMatching(): void
    {
        $this->registry->excludePatterns([
            '/api/v*/health',
            '/tenant/*/config',
            '*.json',
            'webhook/[0-9]*',
        ]);

        $this->assertTrue($this->registry->isExcluded('/api/v1/health'));
        $this->assertTrue($this->registry->isExcluded('/api/v2/health'));
        $this->assertTrue($this->registry->isExcluded('/tenant/abc123/config'));
        $this->assertTrue($this->registry->isExcluded('data.json'));
        $this->assertTrue($this->registry->isExcluded('webhook/12345'));

        $this->assertFalse($this->registry->isExcluded('/api/health'));
        $this->assertFalse($this->registry->isExcluded('/tenant/config'));
        $this->assertFalse($this->registry->isExcluded('data.xml'));
        $this->assertFalse($this->registry->isExcluded('webhook/abc'));
    }

    #[TestDox('Should return empty arrays when no exclusions registered')]
    public function testEmptyExclusions(): void
    {
        $this->assertEmpty($this->registry->getExcludedPaths());
        $this->assertEmpty($this->registry->getExcludedPatterns());
        $this->assertFalse($this->registry->isExcluded('/any/path'));
    }
}
