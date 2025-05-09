<?php

return [
    'name'         => 'Tenant',
    /**
     * IPs that are trusted to make internal requests.
     */
    'trusted_ips'  => explode(',', env('TRUSTED_INTERNAL_IPS', '127.0.0.1,::1')),

    /**
     * Whether to enable multi-tenancy.
     */
    'tenant_cache' => [
        'preload'          => env('SSR_PRELOAD_TENANTS', true),
        'ttl'              => env('SSR_TENANT_TTL', 300),
        'refresh_interval' => env('SSR_TENANT_REFRESH_INTERVAL', 300),
    ],

    /**
     * The tenant migration loops through each table and applies the tenant scope.
     */
    'tables'       => [
        'users'         => [
            'after'          => 'id',
            'cascadeDelete'  => true,
            'dropUnique'     => [
                'email',
                'provider_id',
            ],
            'compoundUnique' => [
                'email',
                'provider_id',
            ],

        ],
        'catalog_items' => [
            'after'         => 'id',
            'cascadeDelete' => true,
        ],
    ],
];
