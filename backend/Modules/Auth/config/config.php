<?php

return [
    'name'                      => 'Auth',
    /**
     * Disable Socialite Authentication
     */
    'disable_socialite'         => env('AUTH_DISABLE_SOCIALITE', false),
    /**
     * User must verify their email before logging in.
     */
    'verify_email_before_login' => env('AUTH_VERIFY_EMAIL_BEFORE_LOGIN', true),
    /**
     * Socialite Configuration
     */
    'socialite'                 => [
        /**
         * Socialite Providers
         *
         * @var array<string>
         */
        'providers'   => explode(
            ',',
            env('SOCIALITE_PROVIDERS', 'google'),
        ),
        /**
         * Socialite Nonce TTL
         *
         * @var int
         */
        'nonce_ttl'   => env('SOCIALITE_NONCE_TTL', 60),
        /**
         * Socialite Token TTL
         *
         * @var int
         */
        'token_ttl'   => env('SOCIALITE_TOKEN_TTL', 60),
        /**
         * HMAC Secret Key
         *
         * @var string
         */
        'hmac_secret' => env('HMAC_SECRET_KEY'),
    ],
];
