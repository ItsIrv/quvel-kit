<?php

return [
    /**
     * Captcha Configuration
     */
    'captcha' => [
        /**
         * Default captcha provider to use.
         * Currently only 'recaptcha_v3' is supported.
         */
        'provider' => env('CAPTCHA_PROVIDER', 'recaptcha_v3'),

        /**
         * Provider-specific configurations
         */
        'providers' => [
            'recaptcha_v3' => [
                /**
                 * reCAPTCHA site key (public)
                 */
                'site_key' => env('RECAPTCHA_SITE_KEY'),

                /**
                 * reCAPTCHA secret key (private)
                 */
                'secret_key' => env('RECAPTCHA_SECRET_KEY'),

                /**
                 * Google verification endpoint
                 */
                'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',

                /**
                 * Minimum score for reCAPTCHA v3 (0.0-1.0)
                 */
                'score_threshold' => env('RECAPTCHA_SCORE_THRESHOLD', 0.5),
            ],
        ],

        /**
         * Global captcha settings
         */

        /**
         * HTTP timeout in seconds for captcha verification
         */
        'timeout' => env('CAPTCHA_TIMEOUT', 30),

        /**
         * Whether captcha verification is enabled globally
         */
        'enabled' => env('CAPTCHA_ENABLED', true),
    ],

    /**
     * HTTP Headers Configuration
     */
    'headers' => [
        /**
         * Custom header for distributed tracing.
         * Set to null to use the default 'X-Trace-ID'
         */
        'trace_id' => env('HEADER_TRACE_ID'),

        /**
         * Custom header for platform detection.
         * Set to null to use the default 'X-Platform'
         */
        'platform' => env('HEADER_PLATFORM'),

        /**
         * Custom header for SSR API key.
         * Set to null to use the default 'X-SSR-Key'
         */
        'ssr_key' => env('HEADER_SSR_KEY'),
    ],

    /**
     * Security Configuration
     */
    'security' => [
        /**
         * Internal request validation settings
         */
        'internal_requests' => [
            /**
             * List of trusted IP addresses for internal requests
             */
            'trusted_ips' => explode(',', env('SECURITY_TRUSTED_IPS', '127.0.0.1,::1')),

            /**
             * API key required for internal requests
             */
            'api_key' => env('SECURITY_API_KEY'),

            /**
             * Bypass IP validation (not recommended for production)
             */
            'disable_ip_check' => env('SECURITY_DISABLE_IP_CHECK', false),

            /**
             * Bypass API key validation (not recommended for production)
             */
            'disable_key_check' => env('SECURITY_DISABLE_KEY_CHECK', false),
        ],
    ],

    /**
     * Logging Configuration
     */
    'logging' => [
        /**
         * Default log channel for contextual logger
         */
        'default_channel' => env('LOG_CHANNEL', 'stack'),

        /**
         * Whether to automatically include trace ID in log context
         */
        'include_trace_id' => env('LOG_INCLUDE_TRACE_ID', true),

        /**
         * Context enrichment settings
         */
        'context_enrichment' => [
            /**
             * Enable automatic context enrichment
             */
            'enabled' => env('LOG_CONTEXT_ENRICHMENT', true),

            /**
             * Sanitize PII in log data
             */
            'sanitize_sensitive_data' => env('LOG_SANITIZE_SENSITIVE', true),
        ],
    ],

    /**
     * Locale Configuration
     */
    'locale' => [
        /**
         * Allowed application locales (comma-separated list)
         */
        'allowed_locales' => explode(',', env('LOCALE_ALLOWED', 'en')),

        /**
         * Default locale when detection fails
         */
        'fallback_locale' => env('LOCALE_FALLBACK', 'en'),

        /**
         * Convert region-specific locales to base language (en-US -> en)
         */
        'normalize_locales' => env('LOCALE_NORMALIZE', true),
    ],

    /**
     * Frontend Integration Configuration
     */
    'frontend' => [
        /**
         * Base URL for frontend application
         */
        'url' => env('FRONTEND_URL', 'http://localhost:3000'),

        /**
         * Custom URL scheme for mobile/desktop deep links (e.g., 'myapp')
         * Use null to disable deep linking
         */
        'custom_scheme' => env('FRONTEND_CUSTOM_SCHEME'),

        /**
         * Internal API URL for server-side requests from SSR
         */
        'internal_api_url' => env('FRONTEND_INTERNAL_API_URL'),
    ],

    /**
     * Distributed Tracing Configuration
     */
    'tracing' => [
        /**
         * Enable distributed tracing with UUID generation
         */
        'enabled' => env('TRACING_ENABLED', true),
    ],
];