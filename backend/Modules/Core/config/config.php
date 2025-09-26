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
         * The verifier will read tenant-specific keys from tenant configuration.
         */
        'provider' => GoogleRecaptchaVerifier::class,

        /**
         * The reCAPTCHA site key (public key).
         */
        'recaptcha_site_key' => env('RECAPTCHA_GOOGLE_SITE_KEY'),

        /**
         * The reCAPTCHA secret key (private key).
         */
        'recaptcha_secret_key' => env('RECAPTCHA_GOOGLE_SECRET'),
    ],

    'privacy'   => [
        /**
         * API key for SSR requests.
         */
        'ssr_api_key'       => env('PRIVACY_SSR_API_KEY'),

        /**
         * IPs that are trusted to make internal requests.
         */
        'trusted_ips'       => explode(',', env('PRIVACY_TRUSTED_INTERNAL_IPS', '127.0.0.1,::1')),

        /**
         * Whether to disable the key check.
         */
        'disable_key_check' => env('PRIVACY_DISABLE_KEY_CHECK', false),

        /**
         * Whether to disable the IP check.
         */
        'disable_ip_check'  => env('PRIVACY_DISABLE_IP_CHECK', false),
    ],
];
