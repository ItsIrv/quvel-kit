<?php

return [
    'name'         => 'Tenant',

    /**
     * SSR tenant cache configuration.
     */
    'tenant_cache' => [
        /**
         * Allows SSR to preload tenants on boot.
         */
        'preload'      => env('TENANT_SSR_PRELOAD_TENANTS', true),

        /**
         * Time for individual tenants to be cached.
         */
        'resolver_ttl' => env('TENANT_SSR_RESOLVER_TTL', 300),

        /**
         * Refresh interval for tenants in cache.
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
     * The tenant migration loops through each table and applies the tenant scope.
     */
    'tables'       => [
        'users'         => [
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
        /**
         * Catalog items table
         */
        'catalog_items' => [
            /**
             * Column after which the tenant_id should be added
             */
            'after'          => 'id',

            /**
             * Whether tenant deletion cascades to this table
             */
            'cascade_delete' => true,
        ],
    ],
];
