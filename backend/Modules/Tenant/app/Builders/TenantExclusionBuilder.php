<?php

namespace Modules\Tenant\Builders;

use Modules\Tenant\Contracts\ExclusionBuilderInterface;
use Modules\Tenant\ValueObjects\TenantExclusionConfig;

/**
 * Fluent builder for tenant exclusion configuration.
 * 
 * Provides a clean, chainable API for configuring paths and patterns
 * that should bypass tenant resolution.
 */
class TenantExclusionBuilder implements ExclusionBuilderInterface
{
    private array $paths = [];
    private array $patterns = [];

    /**
     * Create a new exclusion builder instance.
     *
     * @return static
     */
    public static function create(): static
    {
        return new static();
    }

    /**
     * Add exact paths that should bypass tenant resolution.
     *
     * @param string|array $paths Path or array of paths to exclude
     * @return static
     */
    public function paths(string|array $paths): static
    {
        $pathArray = is_array($paths) ? $paths : [$paths];
        $this->paths = array_unique(array_merge($this->paths, $pathArray));
        return $this;
    }

    /**
     * Add a single exact path that should bypass tenant resolution.
     *
     * @param string $path Path to exclude
     * @return static
     */
    public function path(string $path): static
    {
        return $this->paths([$path]);
    }

    /**
     * Add path patterns that should bypass tenant resolution.
     * 
     * Patterns support wildcards (*, ?, [chars], etc.)
     *
     * @param string|array $patterns Pattern or array of patterns to exclude
     * @return static
     */
    public function patterns(string|array $patterns): static
    {
        $patternArray = is_array($patterns) ? $patterns : [$patterns];
        $this->patterns = array_unique(array_merge($this->patterns, $patternArray));
        return $this;
    }

    /**
     * Add a single path pattern that should bypass tenant resolution.
     *
     * @param string $pattern Pattern to exclude (supports wildcards)
     * @return static
     */
    public function pattern(string $pattern): static
    {
        return $this->patterns([$pattern]);
    }

    /**
     * Build and return the exclusion configuration.
     *
     * @return TenantExclusionConfig
     */
    public function build(): TenantExclusionConfig
    {
        return new TenantExclusionConfig(
            paths: $this->paths,
            patterns: $this->patterns
        );
    }
}