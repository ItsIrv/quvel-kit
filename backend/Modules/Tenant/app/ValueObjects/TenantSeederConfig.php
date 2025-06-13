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
         */
        public array $config,
        /**
         * Visibility settings for configuration keys.
         * Values: 'public', 'protected', 'private'
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
     * @return array
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
     * @param array $config
     * @return static
     */
    public static function fromArray(array $config): static
    {
        return new static(
            config: $config['config'] ?? [],
            visibility: $config['visibility'] ?? [],
            priority: $config['priority'] ?? 50
        );
    }
}
