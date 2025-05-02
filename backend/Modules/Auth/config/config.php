<?php

return [
    'name'                      => 'Auth',
    'disable_socialite'         => env('AUTH_DISABLE_SOCIALITE', false),
    'verify_email_before_login' => env('AUTH_VERIFY_EMAIL_BEFORE_LOGIN', true),
    'oauth'                     => [
        'providers'   => explode(
            ',',
            env('SOCIALITE_PROVIDERS', 'google'),
        ),
        'nonce_ttl'   => env(' SOCIALITE_NONCE_TTL', 60),
        'token_ttl'   => env(' SOCIALITE_TOKEN_TTL', 60),
        'hmac_secret' => env('HMAC_SECRET_KEY'),
    ],
];
