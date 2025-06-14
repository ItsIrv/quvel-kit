<?php

namespace Modules\Tenant\Seeders\CoreConfig;

use Modules\Tenant\Contracts\TenantConfigSeederInterface;

/**
 * Core application configuration seeder for basic tenant template.
 *
 * Handles application-level configuration that would traditionally be 
 * considered "core" but is actually tenant-specific.
 */
class CoreApplicationBasicSeeder implements TenantConfigSeederInterface
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

        return $coreConfig;
    }

    /**
     * Get visibility settings for the configuration values.
     *
     * @return array Key-value pairs of config keys and their visibility levels
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
        ];
    }
}