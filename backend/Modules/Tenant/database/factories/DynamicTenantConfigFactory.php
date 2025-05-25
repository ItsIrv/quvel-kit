<?php

namespace Modules\Tenant\database\factories;

use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\Services\TenantConfigSeederRegistry;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

/**
 * Factory for creating dynamic tenant configurations.
 * This factory only provides minimal tenant configuration.
 * Modules should register their own config seeders via TenantServiceProvider::registerConfigSeeder()
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
        $baseConfig = [
            // Minimal tenant identification
            'domain' => $domain,
            'tier' => 'basic',
            
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

        return new DynamicTenantConfig($config, $visibility, 'basic');
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
        // Generate shorter tenant ID (8 chars)
        $tenantId = substr(uniqid(), -8);

        $baseConfig = [
            // Minimal tenant identification
            'domain' => $domain,
            'tier' => 'standard',
            
            // Standard tier - dedicated cache
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

        return new DynamicTenantConfig($config, $visibility, 'standard');
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
        // Generate shorter tenant ID (8 chars)
        $tenantId = substr(uniqid(), -8);

        $baseConfig = [
            // Minimal tenant identification
            'domain' => $apiDomain,
            'tier' => 'premium',
            
            // Premium tier - dedicated database
            'db_database' => $dbDatabase ?? "tenant_{$tenantId}_db",
            'db_username' => $dbUsername ?? "tenant_{$tenantId}",
            'db_password' => $dbPassword ?? bin2hex(random_bytes(16)),
            
            // Dedicated cache
            'cache_prefix' => "tenant_{$tenantId}_",
            
            // Pass through parameters for Core module to use
            '_seed_app_name' => $appName,
            '_seed_mail_from_name' => $mailFromName,
            '_seed_mail_from_address' => $mailFromAddress,
            '_seed_capacitor_scheme' => $capacitorScheme,
        ];

        // Get module-specific config from registry
        $registry = app(TenantConfigSeederRegistry::class);
        $config = $registry->getSeedConfig('premium', $baseConfig);
        
        // Remove seed parameters
        unset(
            $config['_seed_app_name'], 
            $config['_seed_mail_from_name'], 
            $config['_seed_mail_from_address'],
            $config['_seed_capacitor_scheme']
        );

        $baseVisibility = [];
        $visibility = $registry->getSeedVisibility('premium', $baseVisibility);

        return new DynamicTenantConfig($config, $visibility, 'premium');
    }

    /**
     * Create an enterprise tier configuration (fully isolated).
     */
    public static function createEnterpriseTier(
        string $apiDomain,
        string $appName = 'QuVel',
        array $overrides = [],
    ): DynamicTenantConfig {
        // Generate shorter tenant ID (8 chars)
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
            // Minimal tenant identification
            'domain' => $apiDomain,
            'tier' => 'enterprise',
            
            // Enterprise gets full configuration control
            'db_connection' => 'mysql',
            'db_host' => env('DB_HOST', '127.0.0.1'),
            'db_port' => env('DB_PORT', 3306),
            'db_database' => "tenant_{$tenantId}_db",
            'db_username' => "tenant_{$tenantId}",
            'db_password' => bin2hex(random_bytes(16)),
            'cache_store' => 'redis',
            'cache_prefix' => "tenant_{$tenantId}_",
            'session_driver' => 'redis',
            'redis_host' => env('REDIS_HOST', '127.0.0.1'),
            'redis_port' => env('REDIS_PORT', 6379),
            'mail_mailer' => 'smtp',
            'mail_host' => env('MAIL_HOST', 'smtp.mailgun.org'),
            'mail_port' => env('MAIL_PORT', 587),
            
            // Pass through parameters
            '_seed_app_name' => $appName,
        ], $overrides, $seedParams);

        // Get module-specific config from registry
        $registry = app(TenantConfigSeederRegistry::class);
        $config = $registry->getSeedConfig('enterprise', $baseConfig);
        
        // Remove all seed parameters
        foreach (array_keys($config) as $key) {
            if (str_starts_with($key, '_seed_')) {
                unset($config[$key]);
            }
        }

        $baseVisibility = [];
        $visibility = $registry->getSeedVisibility('enterprise', $baseVisibility);

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
        // Only include minimal configs
        $config = [
            'tier' => $tier,
        ];

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

        // Apply any overrides
        $config = array_merge($config, $overrides);

        // Get module-specific config from registry
        $registry = app(TenantConfigSeederRegistry::class);
        $config = $registry->getSeedConfig($tier, $config);

        $baseVisibility = [];
        $visibility = $registry->getSeedVisibility($tier, $baseVisibility);

        return new DynamicTenantConfig($config, $visibility, $tier);
    }
}