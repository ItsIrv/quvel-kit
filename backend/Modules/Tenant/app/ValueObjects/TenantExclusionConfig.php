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
         * @var array<int, string>
         */
        public array $paths = [],
        /**
         * Path patterns that should bypass tenant resolution.
         * Patterns support wildcards (*, ?, [chars], etc.)
         * @var array<int, string>
         */
        public array $patterns = [],
    ) {
    }

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array<string, mixed>
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
     * @param array<string, mixed> $config
     * @return static
     */
    public static function fromArray(array $config): static
    {
        /** @phpstan-ignore-next-line */
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
        return $this->paths !== [] || $this->patterns !== [];
    }

    /**
     * Merge with another exclusion config.
     *
     * @param TenantExclusionConfig $other
     * @return static
     */
    public function merge(TenantExclusionConfig $other): static
    {
        /** @phpstan-ignore-next-line */
        return new static(
            paths: array_unique(array_merge($this->paths, $other->paths)),
            patterns: array_unique(array_merge($this->patterns, $other->patterns)),
        );
    }
}
