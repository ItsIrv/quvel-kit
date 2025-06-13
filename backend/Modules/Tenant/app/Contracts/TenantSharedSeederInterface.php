<?php

namespace Modules\Tenant\Contracts;

/**
 * Contract for tenant shared configuration seeders.
 *
 * Shared seeders provide configuration that applies to all tenant templates.
 * They are typically used for cross-cutting concerns like API keys,
 * third-party service configurations, etc.
 */
interface TenantSharedSeederInterface
{
    /**
     * Generate shared configuration values that apply to all templates.
     *
     * @param string $template The tenant template for context
     * @param array $baseConfig The base configuration to build upon
     * @return array The shared configuration values to seed
     */
    public function getSharedConfig(string $template, array $baseConfig): array;

    /**
     * Get visibility settings for the shared configuration values.
     *
     * Visibility levels: 'public', 'protected', 'private'
     *
     * @return array Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array;

    /**
     * Get the priority for this shared seeder.
     *
     * Lower numbers run first (e.g., 10 runs before 20).
     *
     * @return int The priority level
     */
    public function getPriority(): int;
}
