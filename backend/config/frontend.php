<?php

return [
    'url'              => env('FRONTEND_URL', 'http://localhost:3000'),
    'internal_api_url' => env('FRONTEND_INTERNAL_API_URL', 'http://localhost:3000'),
    'capacitor_scheme' => env('FRONTEND_CAPACITOR_SCHEME', null),
    'allowed_locales'  => explode(',', env('FRONTEND_ALLOWED_LOCALES', 'en-US,es-MX')),
];
