<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OTP Configuration
    |--------------------------------------------------------------------------
    |
    | These options control the one-time password generation and validation
    | for phone verification.
    |
    */

    'otp'        => [
        'length'       => 6,
        'ttl'          => 300, // 5 minutes in seconds
        'cache_prefix' => 'phone_otp:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Phone Validation
    |--------------------------------------------------------------------------
    |
    | Configuration for phone number validation and formatting.
    |
    */

    'phone'      => [
        'default_country'     => 'US',
        'supported_countries' => ['US'],
        'formats'             => [
            'US' => [
                'min_length' => 10,
                'max_length' => 10,
                'pattern'    => '/^[2-9][0-9]{2}[2-9][0-9]{6}$/',
            ],
            // 'MX' => [
            //     'min_length' => 10,
            //     'max_length' => 12,
            //     'pattern' => '/^(\+?52)?[1-9][0-9]{9}$/',
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Control how many verification attempts are allowed within a time period.
    |
    */

    'rate_limit' => [
        'attempts'      => 3,
        'decay_minutes' => 60,
        'key_prefix'    => 'phone_verification:',
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Provider Settings
    |--------------------------------------------------------------------------
    |
    | Future extensibility for SMS providers. Currently for development,
    | OTPs will be logged instead of sent via SMS.
    |
    */

    'sms'        => [
        'provider' => env('PHONE_SMS_PROVIDER', 'log'),
    ],
];
