<?php

return [
    'name'         => 'Tenant',
    'multi_tenant' => env('SSR_MULTI_TENANT', true),
    'tenant_cache' => [
        'preload'          => env('SSR_PRELOAD_TENANTS', true),
        'ttl'              => env('SSR_TENANT_TTL', 300),
        'refresh_interval' => env('SSR_TENANT_REFRESH_INTERVAL', 300),
    ],
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
