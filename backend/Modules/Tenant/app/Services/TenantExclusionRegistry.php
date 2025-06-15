<?php

namespace Modules\Tenant\Services;

class TenantExclusionRegistry
{
    /**
     * Paths that should bypass tenant resolution.
     * @var array<int, string>
     */
    protected array $excludedPaths = [];

    /**
     * Path patterns that should bypass tenant resolution.
     * @var array<int, string>
     */
    protected array $excludedPatterns = [];

    /**
     * Add exact paths to exclude from tenant resolution.
     * @param string|array<int, string> $paths
     */
    public function excludePaths(string|array $paths): void
    {
        $paths               = is_array($paths) ? $paths : [$paths];
        $this->excludedPaths = array_unique(array_merge($this->excludedPaths, $paths));
    }

    /**
     * Add path patterns to exclude from tenant resolution.
     * @param string|array<int, string> $patterns
     */
    public function excludePatterns(string|array $patterns): void
    {
        $patterns               = is_array($patterns) ? $patterns : [$patterns];
        $this->excludedPatterns = array_unique(array_merge($this->excludedPatterns, $patterns));
    }

    /**
     * Get all excluded paths.
     * @return array<int, string>
     */
    public function getExcludedPaths(): array
    {
        return $this->excludedPaths;
    }

    /**
     * Get all excluded patterns.
     * @return array<int, string>
     */
    public function getExcludedPatterns(): array
    {
        return $this->excludedPatterns;
    }

    /**
     * Check if a path should be excluded.
     */
    public function isExcluded(string $path): bool
    {
        // Check exact matches
        if (in_array($path, $this->excludedPaths, true)) {
            return true;
        }

        // Check pattern matches
        foreach ($this->excludedPatterns as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }
}
