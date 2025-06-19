<?php

namespace Modules\Tenant\Seeders\CoreConfig;

use Modules\Tenant\Contracts\TenantConfigSeederInterface;

/**
 * Core application configuration seeder for isolated tenant template.
 *
 * Handles application-level configuration for isolated tenants including
 * internal API URLs and enhanced domain handling.
 */
class CoreApplicationIsolatedSeeder implements TenantConfigSeederInterface
{
    /**
     * Generate configuration values for isolated template.
     *
     * @param string $template The tenant template
     * @param array<string, mixed> $baseConfig The base configuration to build upon
     * @return array<string, mixed> The configuration values to seed
     */
    public function getConfig(string $template, array $baseConfig): array
    {
        // Extract domain info from existing config
        $domain      = $baseConfig['domain'] ?? 'example.com';
        $apiUrl      = "https://$domain";
        $frontendUrl = 'https://' . str_replace('api.', '', $domain);

        // Core application configuration with reasonable defaults
        $coreConfig = [
            'app_name'     => $baseConfig['app_name'] ?? 'QuVel',
            'app_url'      => $apiUrl,
            'frontend_url' => $frontendUrl,
        ];

        // Add mail configuration with sensible defaults
        $coreConfig['mail_from_name'] = $baseConfig['mail_from_name']
            ?? $coreConfig['app_name'] . ' Support';

        $coreConfig['mail_from_address'] = $baseConfig['mail_from_address']
            ?? 'support@' . str_replace(['https://', 'http://', 'api.'], '', $domain);

        // Add capacitor scheme if provided
        if (isset($baseConfig['capacitor_scheme'])) {
            $coreConfig['capacitor_scheme'] = $baseConfig['capacitor_scheme'];
        }

        // Add internal API URL for isolated template
        if (!isset($baseConfig['internal_api_url'])) {
            $coreConfig['internal_api_url'] = $this->generateInternalApiUrl($domain, $apiUrl);
        }

        // Add assets configuration if provided
        if (isset($baseConfig['assets'])) {
            $coreConfig['assets'] = $baseConfig['assets'];
        }

        // Add meta configuration if provided
        if (isset($baseConfig['meta'])) {
            $coreConfig['meta'] = $baseConfig['meta'];
        }

        return $coreConfig;
    }

    /**
     * Get visibility settings for the configuration values.
     *
     * @return array<string, mixed> Key-value pairs of config keys and their visibility levels
     */
    public function getVisibility(): array
    {
        return [
            'app_name'          => 'public',
            'app_url'           => 'public',
            'frontend_url'      => 'protected',
            'mail_from_name'    => 'private',
            'mail_from_address' => 'private',
            'capacitor_scheme'  => 'protected',
            'internal_api_url'  => 'protected',
            'assets'            => 'public', // Assets must be public for frontend injection
            'meta'              => 'public', // Meta must be public for frontend meta tags
        ];
    }

    /**
     * Generate internal API URL for isolated tenants.
     *
     * @param string $domain The tenant domain
     * @param string $apiUrl The external API URL
     * @return string Internal API URL
     */
    private function generateInternalApiUrl(string $domain, string $apiUrl): string
    {
        // Special handling for specific isolated domains (like the seeder does)
        if ($domain === 'api-lan') {
            return 'http://api-lan:8000';
        }

        // Extract just the domain part for internal API
        $internalDomain = str_replace(['https://', 'http://'], '', $apiUrl);
        return "http://{$internalDomain}:8000";
    }
}
