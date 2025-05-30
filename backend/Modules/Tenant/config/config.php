<?php

use Modules\Tenant\Services\HostResolver;

return [
    'name'         => 'Tenant',

    /**
     * Enable or disable the tier system.
     * When disabled, all tenants have access to all features and no limits are enforced.
     */
    'enable_tiers' => env('TENANT_ENABLE_TIERS', false),

    /**
     * Tenant resolver.
     */
    'resolver'     => HostResolver::class,

    /**
     * Paths that should bypass tenant resolution.
     * These paths will not have tenant context applied.
     * Modules should use TenantServiceProvider::excludePaths() to register paths dynamically.
     */
    'excluded_paths' => [
        // Static exclusions can be added here if needed
    ],

    /**
     * Path patterns that should bypass tenant resolution.
     * Uses Laravel's request->is() method for pattern matching.
     * Supports wildcards: admin/* will match admin/setup, admin/tenants, etc.
     * Modules should use TenantServiceProvider::excludePatterns() to register patterns dynamically.
     */
    'excluded_patterns' => [
        // Static exclusions can be added here if needed
    ],

    /**
     * SSR tenant cache configuration.
     */
    'tenant_cache' => [
        /**
         * Allows SSR to preload tenants on boot.
         */
        'preload'      => env('TENANT_SSR_PRELOAD_TENANTS', true),

        /**
         * Time for individual tenants to be cached by the domain resolver.
         */
        'resolver_ttl' => env('TENANT_SSR_RESOLVER_TTL', 300),

        /**
         * Time for tenants dump endpoint to be cached.
         */
        'cache_ttl'    => env('TENANT_SSR_CACHE_TTL', 300),
    ],

    'privacy'      => [
        /**
         * API key for SSR requests.
         */
        'ssr_api_key'       => env('TENANT_PRIVACY_SSR_API_KEY'),

        /**
         * IPs that are trusted to make internal requests.
         */
        'trusted_ips'       => explode(',', env('TENANT_PRIVACY_TRUSTED_INTERNAL_IPS', '127.0.0.1,::1')),

        /**
         * Whether to disable the key check.
         */
        'disable_key_check' => env('TENANT_PRIVACY_DISABLE_KEY_CHECK', false),

        /**
         * Whether to disable the IP check.
         */
        'disable_ip_check'  => env('TENANT_PRIVACY_DISABLE_IP_CHECK', false),
    ],

    /**
     * Configuration pipeline pipes.
     * These are applied in order of priority (higher priority runs first).
     * Modules can register their own pipes in their service providers.
     */
    'config_pipes' => [
        \Modules\Tenant\Pipes\CoreConfigPipe::class,
        \Modules\Tenant\Pipes\DatabaseConfigPipe::class,
        \Modules\Tenant\Pipes\CacheConfigPipe::class,
        \Modules\Tenant\Pipes\RedisConfigPipe::class,
        \Modules\Tenant\Pipes\SessionConfigPipe::class,
        \Modules\Tenant\Pipes\MailConfigPipe::class,
        \Modules\Tenant\Pipes\QueueConfigPipe::class,
        \Modules\Tenant\Pipes\FilesystemConfigPipe::class,
        \Modules\Tenant\Pipes\BroadcastingConfigPipe::class,
        \Modules\Tenant\Pipes\LoggingConfigPipe::class,
        \Modules\Tenant\Pipes\ServicesConfigPipe::class,
    ],

    /**
     * Tenant tiers define the level of isolation for each tenant.
     * You can customize these based on your business needs.
     */
    'tiers'        => [
        'basic'      => [
            'description' => 'Shared database, shared cache, shared resources',
            'features'    => ['row_isolation'],
        ],
        'standard'   => [
            'description' => 'Shared database with dedicated cache',
            'features'    => ['row_isolation', 'dedicated_cache'],
        ],
        'premium'    => [
            'description' => 'Dedicated database and cache',
            'features'    => ['database_isolation', 'dedicated_cache'],
        ],
        'enterprise' => [
            'description' => 'Fully isolated infrastructure',
            'features'    => ['database_isolation', 'dedicated_cache', 'custom_domain', 'sla'],
        ],
    ],

    /**
     * The tenant migration loops through each table and applies the tenant scope.
     * Modules should register their tables using TenantServiceProvider::registerTenantTable()
     * rather than adding them here.
     */
    'tables'       => [
        'users' => [
            /**
             * Column after which the tenant_id should be added
             */
            'after'                     => 'id',

            /**
             * Whether tenant deletion cascades to this table
             */
            'cascade_delete'            => true,

            /**
             * List of individual unique constraints to drop before adding tenant-specific compound keys
             */
            'drop_uniques'              => [
                ['email'],
                ['provider_id'],
            ],

            /**
             * Unique constraints that should include tenant_id
             * Each entry is an array of columns that should be unique together within a tenant
             */
            'tenant_unique_constraints' => [
                ['email'],
                ['provider_id'],
                ['email', 'provider_id'],
            ],
        ],
    ],

    /**
     * Tier-specific limits for resource usage.
     * You can customize these based on your business model.
     */
    'tier_limits'  => [
        'basic'      => [
            'users'                 => 5,
            'storage'               => 1024 * 1024 * 100, // 100MB in bytes
            'api_calls_per_hour'    => 1000,
            'queue_jobs_per_hour'   => 100,
            'broadcast_connections' => 10,
            'file_uploads_per_day'  => 50,
        ],
        'standard'   => [
            'users'                 => 25,
            'storage'               => 1024 * 1024 * 1024, // 1GB in bytes
            'api_calls_per_hour'    => 10000,
            'queue_jobs_per_hour'   => 1000,
            'broadcast_connections' => 100,
            'file_uploads_per_day'  => 500,
        ],
        'premium'    => [
            'users'                 => 100,
            'storage'               => 1024 * 1024 * 1024 * 10, // 10GB in bytes
            'api_calls_per_hour'    => 100000,
            'queue_jobs_per_hour'   => 10000,
            'broadcast_connections' => 1000,
            'file_uploads_per_day'  => 5000,
        ],
        'enterprise' => [
            'users'                 => PHP_INT_MAX,
            'storage'               => PHP_INT_MAX,
            'api_calls_per_hour'    => PHP_INT_MAX,
            'queue_jobs_per_hour'   => PHP_INT_MAX,
            'broadcast_connections' => PHP_INT_MAX,
            'file_uploads_per_day'  => PHP_INT_MAX,
        ],
    ],

    /**
     * Tier-specific configuration defaults.
     * These are applied when a tenant doesn't have specific overrides.
     */
    'tier_configs' => [
        'basic'      => [
            'queue_retry_after'  => 60,
            'log_retention_days' => 7,
            // 'session_lifetime'   => 120,
        ],
        'standard'   => [
            'queue_retry_after'  => 90,
            'log_retention_days' => 30,
            // 'session_lifetime'   => 240,
        ],
        'premium'    => [
            'queue_retry_after'  => 120,
            'log_retention_days' => 90,
            // 'session_lifetime'   => 480,
        ],
        'enterprise' => [
            'queue_retry_after'  => 180,
            'log_retention_days' => 365,
            // 'session_lifetime'   => 1440,
        ],
    ],
];
