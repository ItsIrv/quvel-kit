<?php

namespace Modules\Auth\Seeders;

use Modules\Tenant\Contracts\TenantConfigSeederInterface;

/**
 * Auth module seeder for isolated tenant template.
 *
 * Provides enhanced authentication configuration for isolated tenants
 * with unique session cookies and OAuth providers.
 */
class AuthIsolatedSeeder implements TenantConfigSeederInterface
{
    /**
     * Generate configuration values for isolated template.
     *
     * @param string $template The tenant template
     * @param array $baseConfig The base configuration to build upon
     * @return array The configuration values to seed
     */
    public function getConfig(string $template, array $baseConfig): array
    {
        return [
            'socialite_providers' => ['google', 'microsoft'],
            'oauth_credentials'   => $this->buildOAuthCredentials(),
            'session_lifetime'    => 240, // 4 hours for isolated tenants
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
            'socialite_providers' => 'public',
            'oauth_credentials'   => 'private',
            'session_lifetime'    => 'protected',
        ];
    }


    /**
     * Build OAuth credentials configuration.
     *
     * @return array OAuth credentials
     */
    private function buildOAuthCredentials(): array
    {
        return [
            'google'    => [
                'client_id'     => env('GOOGLE_CLIENT_ID', 'your-google-client-id'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET', 'your-google-client-secret'),
            ],
            'microsoft' => [
                'client_id'     => env('MICROSOFT_CLIENT_ID', 'your-microsoft-client-id'),
                'client_secret' => env('MICROSOFT_CLIENT_SECRET', 'your-microsoft-client-secret'),
            ],
        ];
    }
}
