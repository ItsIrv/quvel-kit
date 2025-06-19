<?php

namespace Modules\Tenant\Seeders;

use Modules\Tenant\Contracts\TenantConfigSeederInterface;
use Modules\Tenant\ValueObjects\TenantSeederConfig;

/**
 * Generic tenant seeder implementation.
 *
 * Used by builders to create simple seeders from configuration.
 * For complex logic, create custom seeder classes.
 */
class GenericTenantSeeder implements TenantConfigSeederInterface
{
    public function __construct(
        private readonly TenantSeederConfig $seederConfig,
    ) {
    }

    /**
     * Generate configuration values for a specific template.
     *
     * @param string $template The tenant template (e.g., 'basic', 'isolated')
     * @param array<string, mixed> $baseConfig The base configuration to build upon
     * @return array<string, mixed> The configuration values to seed
     */
    public function getConfig(string $template, array $baseConfig): array
    {
        return $this->seederConfig->config;
    }

    /**
     * Get visibility settings for the configuration values.
     *
     * @return array<string, mixed> Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array
    {
        return $this->seederConfig->visibility;
    }

    /**
     * Get the priority for this seeder.
     *
     * @return int The priority level
     */
    public function getPriority(): int
    {
        return $this->seederConfig->priority;
    }

    /**
     * Get the underlying seeder configuration.
     *
     * @return TenantSeederConfig
     */
    public function getSeederConfig(): TenantSeederConfig
    {
        return $this->seederConfig;
    }
}
