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

    'paths'                    => ['*'],

    'allowed_methods'          => ['GET', 'POST', 'PUT', 'PATCH', 'OPTIONS'],

    'allowed_origins'          => [
        // Quasar on Docker
        'https://quvel.127.0.0.1.nip.io',
        // Quasar Local Machine
        'https://quvel.127.0.0.1.nip.io:3000',
        'https://second-tenant.quvel.127.0.0.1.nip.io',
        // Local SSR
        'https://second-tenant.quvel.127.0.0.1.nip.io:3000',
        // Local SPA
        'https://second-tenant.quvel.127.0.0.1.nip.io:3001',
        // Local Capacitor
        'https://second-tenant.quvel.127.0.0.1.nip.io:3002',
        // Local Electron
        'http://second-tenant.quvel.127.0.0.1.nip.io:3003',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers'          => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-XSRF-TOKEN'],

    'exposed_headers'          => [],

    'max_age'                  => 0,

    'supports_credentials'     => true,

];
