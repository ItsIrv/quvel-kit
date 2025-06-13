<?php

namespace Modules\Tenant\ValueObjects;

/**
 * Immutable value object representing tenant exclusion configuration.
 *
 * Contains paths and patterns that should bypass tenant resolution.
 */
class TenantExclusionConfig
{
    public function __construct(
        /**
         * Exact paths that should bypass tenant resolution.
         */
        public array $paths = [],
        /**
         * Path patterns that should bypass tenant resolution.
         * Patterns support wildcards (*, ?, [chars], etc.)
         */
        public array $patterns = [],
    ) {
    }

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'paths'    => $this->paths,
            'patterns' => $this->patterns,
        ];
    }

    /**
     * Create from array format for backward compatibility.
     *
     * @param array $config
     * @return static
     */
    public static function fromArray(array $config): static
    {
        return new static(
            paths: $config['paths'] ?? [],
            patterns: $config['patterns'] ?? []
        );
    }

    /**
     * Check if this config has any exclusions defined.
     *
     * @return bool
     */
    public function hasExclusions(): bool
    {
        return !empty($this->paths) || !empty($this->patterns);
    }

    /**
     * Merge with another exclusion config.
     *
     * @param TenantExclusionConfig $other
     * @return static
     */
    public function merge(TenantExclusionConfig $other): static
    {
        return new static(
            paths: array_unique(array_merge($this->paths, $other->paths)),
            patterns: array_unique(array_merge($this->patterns, $other->patterns)),
        );
    }
}
