<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Table Name
    |--------------------------------------------------------------------------
    |
    | The name of the table where tenant data will be stored.
    |
    */
    'table_name' => env('TENANT_TABLE_NAME', 'tenants'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    |
    | The model class to use for tenants. You can extend the base model
    | to add custom functionality.
    |
    */
    'model' => \Quvel\Tenant\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | Default Resolver
    |--------------------------------------------------------------------------
    |
    | The default strategy for resolving the current tenant.
    | Available: 'domain', 'subdomain', 'path', 'header'
    |
    */
    'default_resolver' => env('TENANT_RESOLVER', 'domain'),

    /*
    |--------------------------------------------------------------------------
    | Resolver Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for different tenant resolution strategies.
    |
    */
    'resolvers' => [
        'domain' => [
            'cache_ttl' => 300, // 5 minutes
        ],
        'subdomain' => [
            'cache_ttl' => 300,
        ],
        'path' => [
            'cache_ttl' => 300,
            'segment' => 1, // URL segment position
        ],
        'header' => [
            'cache_ttl' => 300,
            'header_name' => 'X-Tenant-ID',
        ],
    ],
];