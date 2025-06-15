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
     * @param array<string, mixed> $baseConfig The base configuration to build upon
     * @return array<string, mixed> The shared configuration values to seed
     */
    public function getSharedConfig(string $template, array $baseConfig): array;

    /**
     * Get visibility settings for the shared configuration values.
     *
     * Visibility levels: 'public', 'protected', 'private'
     *
     * @return array<string, mixed> Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array;

}
