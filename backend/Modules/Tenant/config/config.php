<?php

use Modules\Tenant\Services\HostResolver;

return [
    'name'         => 'Tenant',


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

];
