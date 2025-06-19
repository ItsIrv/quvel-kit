<?php

namespace Modules\Tenant\ValueObjects;

/**
 * Immutable value object representing tenant seeder configuration.
 *
 * Contains configuration values, visibility settings, and priority for a seeder.
 */
class TenantSeederConfig
{
    public function __construct(
        /**
         * Configuration key-value pairs to seed.
         * @var array<string, mixed>
         */
        public array $config,
        /**
         * Visibility settings for configuration keys.
         * Values: 'public', 'protected', 'private'
         * @var array<string, string>
         */
        public array $visibility,

        /**
         * Priority for this seeder (lower runs first).
         */
        public int $priority = 50,
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
            'config'     => $this->config,
            'visibility' => $this->visibility,
            'priority'   => $this->priority,
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
            config: $config['config'] ?? [],
            visibility: $config['visibility'] ?? [],
            priority: $config['priority'] ?? 50
        );
    }
}
