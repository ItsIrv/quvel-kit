<?php

return [
    'name' => 'Auth',
    'oauth' => [
        'providers' => explode(
            ',',
            env('SOCIALITE_PROVIDERS', 'google'),
        ),
        'nonce_ttl' => env(' SOCIALITE_NONCE_TTL', 60),
        'token_ttl' => env(' SOCIALITE_TOKEN_TTL', 60),
        'hmac_secret' => env('HMAC_SECRET_KEY'),
    ],
];
