<?php

use Modules\Core\Services\Security\GoogleRecaptchaVerifier;

return [
    /**
     * Trace Configuration
     * Controls how distributed tracing headers are handled
     */
    'trace'     => [
        /**
         * When true, tracing is enabled
         */
        'enabled'                  => env('TRACE_ENABLED', true),
        /**
         * When true, only internal requests can set trace headers
         * Set to false during development/testing to allow frontend requests to set trace headers
         */
        'require_internal_request' => env('TRACE_REQUIRE_INTERNAL', true),

        /**
         * When true, a trace ID will always be generated even if none is provided
         */
        'always_generate'          => true,
    ],

    /**
     * Recaptcha Configuration
     */
    'recaptcha' => [
        /**
         * The captcha verifier to use.
         */
        'provider' => GoogleRecaptchaVerifier::class,

        /**
         * Google recaptcha configuration.
         */
        'google'   => [
            'secret' => env('RECAPTCHA_SECRET'),
        ],
    ],
];
