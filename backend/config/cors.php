<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'OPTIONS'],

    'allowed_origins' => [
        // Quasar on Docker
        'https://quvel.127.0.0.1.nip.io',
        'https://quvel.192.168.86.20.nip.io', // REPLACE_WITH_LOCAL_IP
        // LAN Network
        'https://cap-tenant.quvel.192.168.86.20.nip.io', // REPLACE_WITH_LOCAL_IP
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-XSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
