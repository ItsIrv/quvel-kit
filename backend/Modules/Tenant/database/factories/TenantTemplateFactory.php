<?php

namespace Modules\Tenant\Database\Factories;

use Modules\Tenant\Services\TenantConfigSeederRegistry;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

/**
 * Factory for creating tenant configuration templates.
 *
 * This factory focuses on infrastructure templates without application-specific parameters.
 * Application configuration is handled by module seeders which receive parameters directly.
 */
class TenantTemplateFactory
{
    /**
     * Create a basic infrastructure template.
     *
     * Uses shared resources and minimal infrastructure isolation.
     */
    public static function basic(string $domain): DynamicTenantConfig
    {
        $infraConfig = [
            'domain' => $domain,
        ];

        return self::buildTemplate('basic', $infraConfig);
    }

    /**
     * Create a standard infrastructure template.
     *
     * Provides some dedicated resources like cache prefixes.
     */
    public static function standard(string $domain, ?string $cachePrefix = null): DynamicTenantConfig
    {
        $tenantId = substr(uniqid(), -8);

        $infraConfig = [
            'domain'       => $domain,
            'cache_prefix' => $cachePrefix ?? "tenant_{$tenantId}_",
        ];

        return self::buildTemplate('standard', $infraConfig);
    }

    /**
     * Create an isolated infrastructure template.
     *
     * Provides fully dedicated infrastructure resources.
     */
    public static function isolated(
        string $domain,
        array $infrastructureOverrides = [],
    ): DynamicTenantConfig {
        $tenantId = substr(uniqid(), -8);

        $infraConfig = array_merge([
            'domain'         => $domain,

            // Dedicated database configuration
            'db_connection'  => 'mysql',
            'db_host'        => env('DB_HOST', '127.0.0.1'),
            'db_port'        => env('DB_PORT', 3306),
            'db_database'    => "tenant_{$tenantId}_db",
            'db_username'    => "tenant_{$tenantId}",
            'db_password'    => bin2hex(random_bytes(16)),

            // Dedicated cache configuration
            'cache_store'    => 'redis',
            'cache_prefix'   => "tenant_{$tenantId}_",

            // Dedicated session configuration
            'session_driver' => 'redis',

            // Redis configuration
            'redis_host'     => env('REDIS_HOST', '127.0.0.1'),
            'redis_port'     => env('REDIS_PORT', 6379),

            // Mail infrastructure
            'mail_mailer'    => 'smtp',
            'mail_host'      => env('MAIL_HOST', 'smtp.mailgun.org'),
            'mail_port'      => env('MAIL_PORT', 587),
        ], $infrastructureOverrides);

        return self::buildTemplate('isolated', $infraConfig);
    }

    /**
     * Create configuration from environment variables (migration helper).
     *
     * This helps migrate from the old system where all configs were required.
     */
    public static function fromEnvironment(
        string $template = 'basic',
        array $overrides = [],
    ): DynamicTenantConfig {
        $config = [];

        // Database settings (only for isolated template)
        if ($template === 'isolated') {
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

        $config = array_merge($config, $overrides);

        return self::buildTemplate($template, $config);
    }

    /**
     * Build a template by applying module seeders to the base infrastructure config.
     */
    private static function buildTemplate(string $template, array $infraConfig): DynamicTenantConfig
    {
        $registry = app(TenantConfigSeederRegistry::class);
        $config   = $registry->getSeedConfig($template, $infraConfig);

        $baseVisibility = [];
        $visibility     = $registry->getSeedVisibility($template, $baseVisibility);

        return new DynamicTenantConfig($config, $visibility);
    }
}
