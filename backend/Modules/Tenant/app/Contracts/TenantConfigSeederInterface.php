<?php

namespace Modules\Tenant\Contracts;

/**
 * Contract for tenant configuration seeders.
 *
 * Seeders are responsible for providing tenant-specific configuration
 * values and visibility settings for different templates.
 */
interface TenantConfigSeederInterface
{
    /**
     * Generate configuration values for a specific template.
     *
     * @param string $template The tenant template (e.g., 'basic', 'isolated')
     * @param array $baseConfig The base configuration to build upon
     * @return array The configuration values to seed
     */
    public function getConfig(string $template, array $baseConfig): array;

    /**
     * Get visibility settings for the configuration values.
     *
     * Visibility levels: 'public', 'protected', 'private'
     *
     * @return array Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array;

    /**
     * Get the priority for this seeder.
     *
     * Lower numbers run first (e.g., 10 runs before 20).
     *
     * @return int The priority level
     */
    public function getPriority(): int;
}
