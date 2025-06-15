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
     * @param array<string, mixed> $baseConfig The base configuration to build upon
     * @return array<string, mixed> The configuration values to seed
     */
    public function getConfig(string $template, array $baseConfig): array;

    /**
     * Get visibility settings for the configuration values.
     *
     * Visibility levels: 'public', 'protected', 'private'
     *
     * @return array<string, mixed> Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array;

}
