<?php

namespace Modules\Tenant\Services;

class TenantExclusionRegistry
{
    /**
     * Paths that should bypass tenant resolution.
     */
    protected array $excludedPaths = [];

    /**
     * Path patterns that should bypass tenant resolution.
     */
    protected array $excludedPatterns = [];

    /**
     * Add exact paths to exclude from tenant resolution.
     */
    public function excludePaths(string|array $paths): void
    {
        $paths               = is_array($paths) ? $paths : [$paths];
        $this->excludedPaths = array_unique(array_merge($this->excludedPaths, $paths));
    }

    /**
     * Add path patterns to exclude from tenant resolution.
     */
    public function excludePatterns(string|array $patterns): void
    {
        $patterns               = is_array($patterns) ? $patterns : [$patterns];
        $this->excludedPatterns = array_unique(array_merge($this->excludedPatterns, $patterns));
    }

    /**
     * Get all excluded paths.
     */
    public function getExcludedPaths(): array
    {
        return $this->excludedPaths;
    }

    /**
     * Get all excluded patterns.
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
        if (in_array($path, $this->excludedPaths)) {
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
