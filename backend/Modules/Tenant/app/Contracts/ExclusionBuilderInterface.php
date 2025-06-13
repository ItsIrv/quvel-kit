<?php

namespace Modules\Tenant\Contracts;

use Modules\Tenant\ValueObjects\TenantExclusionConfig;

/**
 * Contract for fluent tenant exclusion configuration builders.
 *
 * Provides a clean, chainable API for configuring paths and patterns
 * that should bypass tenant resolution.
 */
interface ExclusionBuilderInterface
{
    /**
     * Add exact paths that should bypass tenant resolution.
     *
     * @param string|array $paths Path or array of paths to exclude
     * @return static
     */
    public function paths(string|array $paths): static;

    /**
     * Add a single exact path that should bypass tenant resolution.
     *
     * @param string $path Path to exclude
     * @return static
     */
    public function path(string $path): static;

    /**
     * Add path patterns that should bypass tenant resolution.
     *
     * Patterns support wildcards (*, ?, [chars], etc.)
     *
     * @param string|array $patterns Pattern or array of patterns to exclude
     * @return static
     */
    public function patterns(string|array $patterns): static;

    /**
     * Add a single path pattern that should bypass tenant resolution.
     *
     * @param string $pattern Pattern to exclude (supports wildcards)
     * @return static
     */
    public function pattern(string $pattern): static;

    /**
     * Build and return the exclusion configuration.
     *
     * @return TenantExclusionConfig
     */
    public function build(): TenantExclusionConfig;
}
