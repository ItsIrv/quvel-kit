<?php

return [
    'name'           => 'TenantAdmin',

    /*
    |--------------------------------------------------------------------------
    | Admin Credentials (Environment Method)
    |--------------------------------------------------------------------------
    |
    | These are used when installation method is 'env'
    |
    */
    'admin_username' => env('TENANT_ADMIN_USERNAME'),
    'admin_password' => env('TENANT_ADMIN_PASSWORD'),
];
