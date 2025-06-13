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
     * @param array $baseConfig The base configuration to build upon
     * @return array The configuration values to seed
     */
    public function getConfig(string $template, array $baseConfig): array
    {
        return [
            'session_cookie' => 'quvel_session',
            'socialite_providers' => [],
            'oauth_credentials' => [],
        ];
    }

    /**
     * Get visibility settings for the configuration values.
     *
     * @return array Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array
    {
        return [
            'session_cookie' => 'protected',
            'socialite_providers' => 'public',
            'session_lifetime' => 'protected',
        ];
    }

    /**
     * Get the priority for this seeder.
     *
     * @return int The priority level
     */
    public function getPriority(): int
    {
        return 20;
    }
}
