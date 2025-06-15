<?php

namespace Modules\Auth\Seeders;

use Modules\Tenant\Contracts\TenantConfigSeederInterface;

/**
 * Auth module seeder for basic tenant template.
 *
 * Provides basic authentication configuration for standard tenants.
 */
class AuthBasicSeeder implements TenantConfigSeederInterface
{
    /**
     * Generate configuration values for basic template.
     *
     * @param string $template The tenant template
     * @param array<string, mixed> $baseConfig The base configuration to build upon
     * @return array<string, mixed> The configuration values to seed
     */
    public function getConfig(string $template, array $baseConfig): array
    {
        return [
            'socialite_providers' => [],
            'oauth_credentials'   => [],
        ];
    }

    /**
     * Get visibility settings for the configuration values.
     *
     * @return array<string, mixed> Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array
    {
        return [
            'socialite_providers' => 'public',
        ];
    }
}
