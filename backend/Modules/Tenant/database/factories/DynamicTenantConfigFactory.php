<?php

namespace Modules\Tenant\database\factories;

use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

/**
 * Factory for creating dynamic tenant configurations.
 * This replaces the old TenantConfigFactory with a more flexible approach.
 */
class DynamicTenantConfigFactory
{
    /**
     * Create a basic tier configuration (shared resources).
     */
    public static function createBasicTier(
        string $domain,
        string $appName = 'QuVel',
        string $mailFromName = 'QuVel Support',
        string $mailFromAddress = 'support@quvel.app',
    ): DynamicTenantConfig {
        $apiUrl = "https://$domain";

        $frontendUrl = 'https://' . str_replace('api.', '', $domain);

        return new DynamicTenantConfig(
            [
                // Required by TenantCache.normalizeConfig
                'app_name'                  => $appName,
                'app_url'                   => $apiUrl,  // Backend URL (becomes apiUrl in frontend)
                'frontend_url'              => $frontendUrl,  // Frontend URL (becomes appUrl in frontend)
                'pusher_app_key'            => env('PUSHER_APP_KEY', ''),
                'pusher_app_cluster'        => env('PUSHER_APP_CLUSTER', 'mt1'),
                'socialite_providers'       => ['google'],
                'session_cookie'            => 'quvel_session',
                'recaptcha_google_site_key' => env('RECAPTCHA_GOOGLE_SITE_KEY', ''),

                // Mail settings
                'mail_from_name'            => $mailFromName,
                'mail_from_address'         => $mailFromAddress,

                // Store tier in config
                'tier'                      => 'basic',
            ],
            [
                'app_name'                  => TenantConfigVisibility::PUBLIC ,
                'app_url'                   => TenantConfigVisibility::PUBLIC ,
                'frontend_url'              => TenantConfigVisibility::PROTECTED ,
                'pusher_app_key'            => TenantConfigVisibility::PUBLIC ,
                'pusher_app_cluster'        => TenantConfigVisibility::PUBLIC ,
                'socialite_providers'       => TenantConfigVisibility::PUBLIC ,
                'recaptcha_google_site_key' => TenantConfigVisibility::PUBLIC ,
                'session_cookie'            => TenantConfigVisibility::PROTECTED ,
            ],
            'basic',
        );
    }

    /**
     * Create a standard tier configuration (dedicated cache).
     */
    public static function createStandardTier(
        string $domain,
        string $appName = 'QuVel',
        string $mailFromName = 'QuVel Support',
        string $mailFromAddress = 'support@quvel.app',
        ?string $cachePrefix = null,
    ): DynamicTenantConfig {
        $tenantId    = uniqid('tenant_');
        $apiUrl      = "https://$domain";
        $frontendUrl = 'https://' . str_replace('api.', '', $domain);

        return new DynamicTenantConfig(
            [
                // Required by TenantCache.normalizeConfig
                'app_name'                  => $appName,
                'app_url'                   => $apiUrl,  // Backend URL (becomes apiUrl in frontend)
                'frontend_url'              => $frontendUrl,  // Frontend URL (becomes appUrl in frontend)
                'pusher_app_key'            => env('PUSHER_APP_KEY', ''),
                'pusher_app_cluster'        => env('PUSHER_APP_CLUSTER', 'mt1'),
                'socialite_providers'       => ['google'],
                'session_cookie'            => "tenant_{$tenantId}_session",
                'recaptcha_google_site_key' => env('RECAPTCHA_GOOGLE_SITE_KEY', ''),

                // Mail settings
                'mail_from_name'            => $mailFromName,
                'mail_from_address'         => $mailFromAddress,

                // Standard tier - dedicated cache
                'cache_prefix'              => $cachePrefix ?? "tenant_{$tenantId}_",

                // Store tier in config
                'tier'                      => 'standard',
            ],
            [
                'app_name'                  => TenantConfigVisibility::PUBLIC ,
                'app_url'                   => TenantConfigVisibility::PUBLIC ,
                'frontend_url'              => TenantConfigVisibility::PROTECTED ,
                'pusher_app_key'            => TenantConfigVisibility::PUBLIC ,
                'pusher_app_cluster'        => TenantConfigVisibility::PUBLIC ,
                'socialite_providers'       => TenantConfigVisibility::PUBLIC ,
                'session_cookie'            => TenantConfigVisibility::PROTECTED ,
                'recaptcha_google_site_key' => TenantConfigVisibility::PUBLIC ,
            ],
            'standard',
        );
    }

    /**
     * Create a premium tier configuration (dedicated database and cache).
     */
    public static function createPremiumTier(
        string $apiDomain,
        string $appName = 'QuVel',
        string $mailFromName = 'QuVel Support',
        string $mailFromAddress = 'support@quvel.app',
        ?string $dbDatabase = null,
        ?string $dbUsername = null,
        ?string $dbPassword = null,
        ?string $capacitorScheme = null,
    ): DynamicTenantConfig {
        $tenantId    = uniqid('tenant_');
        $apiUrl      = "https://$apiDomain";
        $frontendUrl = 'https://' . str_replace('api.', '', $apiDomain);

        return new DynamicTenantConfig(
            [
                // Required for frontend
                'app_name'                  => $appName,
                'app_url'                   => $apiUrl,
                'frontend_url'              => $frontendUrl,
                'pusher_app_key'            => env('PUSHER_APP_KEY', ''),
                'pusher_app_cluster'        => env('PUSHER_APP_CLUSTER', 'mt1'),
                'socialite_providers'       => ['google'],
                'session_cookie'            => "tenant_{$tenantId}_session",
                'recaptcha_google_site_key' => env('RECAPTCHA_GOOGLE_SITE_KEY', ''),

                // Optional capacitor scheme and internal API URL
                'capacitor_scheme'          => $capacitorScheme,
                'internal_api_url'          => "http://{$apiDomain}:8000", // For SSR

                // Mail settings
                'mail_from_name'            => $mailFromName,
                'mail_from_address'         => $mailFromAddress,

                // Premium tier - dedicated database
                'db_database'               => $dbDatabase ?? "tenant_{$tenantId}_db",
                'db_username'               => $dbUsername ?? "tenant_{$tenantId}",
                'db_password'               => $dbPassword ?? bin2hex(random_bytes(16)),

                // Dedicated cache
                'cache_prefix'              => "tenant_{$tenantId}_",

                // Store tier in config
                'tier'                      => 'premium',
            ],
            [
                'app_name'                  => TenantConfigVisibility::PUBLIC ,
                'app_url'                   => TenantConfigVisibility::PUBLIC ,
                'frontend_url'              => TenantConfigVisibility::PROTECTED ,
                'pusher_app_key'            => TenantConfigVisibility::PUBLIC ,
                'pusher_app_cluster'        => TenantConfigVisibility::PUBLIC ,
                'socialite_providers'       => TenantConfigVisibility::PUBLIC ,
                'session_cookie'            => TenantConfigVisibility::PROTECTED ,
                'recaptcha_google_site_key' => TenantConfigVisibility::PUBLIC ,
                'internal_api_url'          => TenantConfigVisibility::PROTECTED ,
            ],
            'premium',
        );
    }

    /**
     * Create an enterprise tier configuration (fully isolated).
     */
    public static function createEnterpriseTier(
        string $apiDomain,
        string $appName = 'QuVel',
        array $overrides = [],
    ): DynamicTenantConfig {
        $tenantId    = uniqid('tenant_');
        $apiUrl      = "https://$apiDomain";
        $frontendUrl = 'https://' . str_replace('api.', '', $apiDomain);

        $config = array_merge([
            // Required for frontend
            'app_name'                  => $appName,
            'app_env'                   => 'production',
            'app_url'                   => $apiUrl,
            'frontend_url'              => $frontendUrl,
            'pusher_app_key'            => env('PUSHER_APP_KEY', ''),
            'pusher_app_cluster'        => env('PUSHER_APP_CLUSTER', 'mt1'),
            'socialite_providers'       => ['google', 'microsoft'],
            'session_cookie'            => "tenant_{$tenantId}_session",
            'recaptcha_google_site_key' => env('RECAPTCHA_GOOGLE_SITE_KEY', ''),

            // Enterprise gets full configuration control
            'db_connection'             => 'mysql',
            'db_host'                   => env('DB_HOST', '127.0.0.1'),
            'db_port'                   => env('DB_PORT', 3306),
            'db_database'               => "tenant_{$tenantId}_db",
            'db_username'               => "tenant_{$tenantId}",
            'db_password'               => bin2hex(random_bytes(16)),
            'cache_store'               => 'redis',
            'cache_prefix'              => "tenant_{$tenantId}_",
            'session_driver'            => 'redis',
            'redis_host'                => env('REDIS_HOST', '127.0.0.1'),
            'redis_port'                => env('REDIS_PORT', 6379),
            'mail_mailer'               => 'smtp',
            'mail_host'                 => env('MAIL_HOST', 'smtp.mailgun.org'),
            'mail_port'                 => env('MAIL_PORT', 587),

            // Store tier in config
            'tier'                      => 'enterprise',
        ], $overrides);

        $visibility = [
            'app_name'                  => TenantConfigVisibility::PUBLIC ,
            'app_url'                   => TenantConfigVisibility::PUBLIC ,
            'frontend_url'              => TenantConfigVisibility::PROTECTED ,
            'session_cookie'            => TenantConfigVisibility::PROTECTED ,
            'pusher_app_key'            => TenantConfigVisibility::PUBLIC ,
            'pusher_app_cluster'        => TenantConfigVisibility::PUBLIC ,
            'socialite_providers'       => TenantConfigVisibility::PUBLIC ,
            'recaptcha_google_site_key' => TenantConfigVisibility::PUBLIC ,
        ];

        return new DynamicTenantConfig($config, $visibility, 'enterprise');
    }

    /**
     * Create configuration from environment variables (migration helper).
     * This helps migrate from the old system where all configs were required.
     */
    public static function createFromEnv(
        string $tier = 'basic',
        array $overrides = [],
    ): DynamicTenantConfig {
        // Only include configs that are actually set in the environment
        $config = [];

        // App settings
        if (env('APP_NAME') !== null) {
            $config['app_name'] = env('APP_NAME', 'QuVel');
        }
        if (env('APP_KEY') !== null) {
            $config['app_key'] = env('APP_KEY');
        }

        // Database settings (only for premium/enterprise tiers)
        if (in_array($tier, ['premium', 'enterprise'])) {
            if (env('DB_CONNECTION') !== null) {
                $config['db_connection'] = env('DB_CONNECTION', 'mysql');
            }
            if (env('DB_HOST') !== null) {
                $config['db_host'] = env('DB_HOST', '127.0.0.1');
            }
            if (env('DB_DATABASE') !== null) {
                $config['db_database'] = env('DB_DATABASE');
            }
        }

        // Mail settings
        if (env('MAIL_FROM_ADDRESS') !== null) {
            $config['mail_from_address'] = env('MAIL_FROM_ADDRESS', 'hello@example.com');
        }
        if (env('MAIL_FROM_NAME') !== null) {
            $config['mail_from_name'] = env('MAIL_FROM_NAME', 'Example');
        }

        // Apply any overrides
        $config = array_merge($config, $overrides);

        // Set visibility for public configs
        $visibility = [
            'app_name' => TenantConfigVisibility::PUBLIC ,
        ];

        return new DynamicTenantConfig($config, $visibility, $tier);
    }
}
