<?php

use Modules\Phone\Drivers\ClickSendSmsDriver;
use Modules\Phone\Drivers\LogSmsDriver;

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
    | SMS Driver Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for SMS drivers.
    |
    */

    'sms'        => [
        'enabled'      => env('SMS_ENABLED', false),
        'default'      => env('SMS_DRIVER', 'log'),
        'otp_template' => 'Your verification code is: :otp',

        'drivers'      => [
            'clicksend' => [
                'class'    => ClickSendSmsDriver::class,
                'username' => env('CLICKSEND_USERNAME'),
                'api_key'  => env('CLICKSEND_API_KEY'),
                'from'     => env('CLICKSEND_FROM', 'Verify'),
            ],

            'log'       => [
                'class' => LogSmsDriver::class,
            ],
        ],
    ],
];
