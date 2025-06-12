<?php

use Modules\Tenant\Services\HostResolver;

return [
    'name'              => 'Tenant',

    /**
     * Tenant resolver.
     */
    'resolver'          => HostResolver::class,

    /**
     * Paths that should bypass tenant resolution.
     * These paths will not have tenant context applied.
     * Modules should use TenantServiceProvider::excludePaths() to register paths dynamically.
     */
    'excluded_paths'    => [
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
    'tenant_cache'      => [
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

    /**
     * Memory cache configuration for Octane environments.
     */
    'memory_cache'      => [
        /**
         * Maximum number of tenants to cache in memory.
         */
        'max_size' => env('TENANT_MEMORY_CACHE_MAX_SIZE', 1000),
    ],

    /**
     * Configuration pipeline pipes.
     * These are now loaded from module config/tenant.php files.
     * This section is kept for reference but is no longer used.
     */
    'config_pipes'      => [
        // Pipes are now automatically loaded from all modules
    ],

    /**
     * The tenant migration loops through each table and applies the tenant scope.
     * Tables are now loaded from module config/tenant.php files.
     * This section is kept for reference but is no longer used.
     */
    'tables'            => [
        // Tables are now automatically loaded from all modules
    ],

];
