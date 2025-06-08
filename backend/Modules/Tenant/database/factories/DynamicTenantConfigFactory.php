<?php

namespace Modules\Tenant\database\factories;

use Modules\Tenant\Services\TenantConfigSeederRegistry;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

/**
 * Factory for creating dynamic tenant configurations.
 * This factory provides simple configuration templates.
 * Modules should register their own config seeders via TenantServiceProvider::registerConfigSeeder()
 */
class DynamicTenantConfigFactory
{
    /**
     * Create a basic configuration (minimal settings).
     */
    public static function createBasic(
        string $domain,
        string $appName = 'QuVel',
        string $mailFromName = 'QuVel Support',
        string $mailFromAddress = 'support@quvel.app',
    ): DynamicTenantConfig {
        $baseConfig = [
            // Basic tenant identification
            'domain' => $domain,
            
            // Pass through parameters for Core module to use
            '_seed_app_name' => $appName,
            '_seed_mail_from_name' => $mailFromName,
            '_seed_mail_from_address' => $mailFromAddress,
        ];

        // Get module-specific config from registry
        $registry = app(TenantConfigSeederRegistry::class);
        $config = $registry->getSeedConfig('basic', $baseConfig);
        
        // Remove seed parameters
        unset($config['_seed_app_name'], $config['_seed_mail_from_name'], $config['_seed_mail_from_address']);

        $baseVisibility = [];
        $visibility = $registry->getSeedVisibility('basic', $baseVisibility);

        return new DynamicTenantConfig($config, $visibility);
    }

    /**
     * Create a standard configuration (with some dedicated resources).
     */
    public static function createStandard(
        string $apiDomain,
        string $appName = 'QuVel',
        string $mailFromName = 'QuVel Support',
        string $mailFromAddress = 'support@quvel.app',
        ?string $cachePrefix = null,
    ): DynamicTenantConfig {
        // Generate unique tenant ID for resource naming
        $tenantId = substr(uniqid(), -8);

        $baseConfig = [
            // Standard tenant identification
            'domain' => $apiDomain,
            
            // Dedicated cache configuration
            'cache_prefix' => $cachePrefix ?? "tenant_{$tenantId}_",
            
            // Pass through parameters for Core module to use
            '_seed_app_name' => $appName,
            '_seed_mail_from_name' => $mailFromName,
            '_seed_mail_from_address' => $mailFromAddress,
        ];

        // Get module-specific config from registry
        $registry = app(TenantConfigSeederRegistry::class);
        $config = $registry->getSeedConfig('standard', $baseConfig);
        
        // Remove seed parameters
        unset($config['_seed_app_name'], $config['_seed_mail_from_name'], $config['_seed_mail_from_address']);

        $baseVisibility = [];
        $visibility = $registry->getSeedVisibility('standard', $baseVisibility);

        return new DynamicTenantConfig($config, $visibility);
    }

    /**
     * Create an isolated configuration (fully dedicated resources).
     */
    public static function createIsolated(
        string $apiDomain,
        string $appName = 'QuVel',
        array $overrides = [],
    ): DynamicTenantConfig {
        // Generate unique tenant ID for resource naming
        $tenantId = substr(uniqid(), -8);

        // Extract seed parameters from overrides
        $seedParams = [];
        foreach ($overrides as $key => $value) {
            if (str_starts_with($key, '_seed_')) {
                $seedParams[$key] = $value;
                unset($overrides[$key]);
            }
        }

        $baseConfig = array_merge([
            // Isolated tenant identification
            'domain' => $apiDomain,
            
            // Dedicated database configuration
            'db_connection' => 'mysql',
            'db_host' => env('DB_HOST', '127.0.0.1'),
            'db_port' => env('DB_PORT', 3306),
            'db_database' => "tenant_{$tenantId}_db",
            'db_username' => "tenant_{$tenantId}",
            'db_password' => bin2hex(random_bytes(16)),
            
            // Dedicated cache configuration
            'cache_store' => 'redis',
            'cache_prefix' => "tenant_{$tenantId}_",
            
            // Dedicated session configuration
            'session_driver' => 'redis',
            
            // Redis configuration
            'redis_host' => env('REDIS_HOST', '127.0.0.1'),
            'redis_port' => env('REDIS_PORT', 6379),
            
            // Mail configuration
            'mail_mailer' => 'smtp',
            'mail_host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'mail_port' => env('MAIL_PORT', 587),
            
            // Pass through parameters
            '_seed_app_name' => $appName,
        ], $overrides, $seedParams);

        // Get module-specific config from registry
        $registry = app(TenantConfigSeederRegistry::class);
        $config = $registry->getSeedConfig('isolated', $baseConfig);
        
        // Remove all seed parameters
        foreach (array_keys($config) as $key) {
            if (str_starts_with($key, '_seed_')) {
                unset($config[$key]);
            }
        }

        $baseVisibility = [];
        $visibility = $registry->getSeedVisibility('isolated', $baseVisibility);

        return new DynamicTenantConfig($config, $visibility);
    }

    /**
     * Create configuration from environment variables (migration helper).
     * This helps migrate from the old system where all configs were required.
     */
    public static function createFromEnv(
        string $template = 'basic',
        array $overrides = [],
    ): DynamicTenantConfig {
        // Only include minimal configs
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

        // Apply any overrides
        $config = array_merge($config, $overrides);

        // Get module-specific config from registry
        $registry = app(TenantConfigSeederRegistry::class);
        $config = $registry->getSeedConfig($template, $config);

        $baseVisibility = [];
        $visibility = $registry->getSeedVisibility($template, $baseVisibility);

        return new DynamicTenantConfig($config, $visibility);
    }
}