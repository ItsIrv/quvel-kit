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
        $sessionCookie = $this->generateSessionCookie($baseConfig);

        return [
            'session_cookie' => $sessionCookie,
            'socialite_providers' => ['google', 'microsoft'],
            'oauth_credentials' => $this->buildOAuthCredentials(),
            'session_lifetime' => 240, // 4 hours for isolated tenants
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
            'oauth_credentials' => 'private',
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

    /**
     * Generate a unique session cookie name for the tenant.
     *
     * @param array $config Base configuration
     * @return string Session cookie name
     */
    private function generateSessionCookie(array $config): string
    {
        $sessionCookie = 'quvel_session';

        if (isset($config['cache_prefix'])) {
            // Extract just the unique ID part from cache_prefix (e.g., "tenant_68337c1aad007_" -> "68337c1aad007")
            if (preg_match('/tenant_([a-z0-9]+)_?/i', $config['cache_prefix'], $matches)) {
                $tenantId = $matches[1];
                // Create a shorter, cleaner session cookie name
                $sessionCookie = "quvel_{$tenantId}";
            } else {
                // Fallback to a simple unique session name
                $sessionCookie = 'quvel_' . substr(md5($config['cache_prefix']), 0, 8);
            }
        }

        return $sessionCookie;
    }

    /**
     * Build OAuth credentials configuration.
     *
     * @return array OAuth credentials
     */
    private function buildOAuthCredentials(): array
    {
        return [
            'google' => [
                'client_id' => env('GOOGLE_CLIENT_ID', 'your-google-client-id'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET', 'your-google-client-secret'),
            ],
            'microsoft' => [
                'client_id' => env('MICROSOFT_CLIENT_ID', 'your-microsoft-client-id'),
                'client_secret' => env('MICROSOFT_CLIENT_SECRET', 'your-microsoft-client-secret'),
            ],
        ];
    }
}
