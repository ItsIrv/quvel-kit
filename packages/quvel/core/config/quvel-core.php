<?php

return [
    /**
     * Captcha Configuration
     */
    'captcha' => [
        /**
         * Default captcha provider to use.
         * Available: 'recaptcha_v3'
         */
        'provider' => env('CAPTCHA_PROVIDER', 'recaptcha_v3'),

        /**
         * Provider-specific configurations
         */
        'providers' => [
            'recaptcha_v3' => [
                'site_key' => env('RECAPTCHA_SITE_KEY'),
                'secret_key' => env('RECAPTCHA_SECRET_KEY'),
                'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
                'score_threshold' => env('RECAPTCHA_SCORE_THRESHOLD', 0.5),
            ],
        ],

        /**
         * Global captcha settings
         */
        'timeout' => env('CAPTCHA_TIMEOUT', 30),
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
         * Custom header for mobile framework detection.
         * Set to null to use the default 'X-Capacitor'
         */
        'capacitor' => env('HEADER_CAPACITOR'),

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
            'trusted_ips' => explode(',', env('SECURITY_TRUSTED_IPS', '127.0.0.1,::1')),
            'api_key' => env('SECURITY_API_KEY'),
            'disable_ip_check' => env('SECURITY_DISABLE_IP_CHECK', false),
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
            'enabled' => env('LOG_CONTEXT_ENRICHMENT', true),
            'sanitize_sensitive_data' => env('LOG_SANITIZE_SENSITIVE', true),
        ],
    ],

    /**
     * Locale Configuration
     */
    'locale' => [
        /**
         * Locale detection strategy
         */
        'strategy' => env('LOCALE_STRATEGY', 'header_with_fallback'),

        /**
         * Allowed application locales
         */
        'allowed_locales' => explode(',', env('LOCALE_ALLOWED', 'en')),

        /**
         * Fallback locale
         */
        'fallback_locale' => env('LOCALE_FALLBACK', 'en'),

        /**
         * Whether to normalize locales (en-US -> en)
         */
        'normalize_locales' => env('LOCALE_NORMALIZE', true),
    ],

    /**
     * Middleware Configuration
     */
    'middleware' => [
        /**
         * Whether to automatically register middleware
         */
        'auto_register' => env('CORE_MIDDLEWARE_AUTO_REGISTER', true),

        /**
         * Middleware groups to apply locale middleware to
         */
        'locale_groups' => explode(',', env('CORE_LOCALE_MIDDLEWARE_GROUPS', 'web,api')),
    ],

    /**
     * Frontend Integration Configuration
     */
    'frontend' => [
        /**
         * Base frontend URL for redirects
         */
        'url' => env('FRONTEND_URL', 'http://localhost:3000'),

        /**
         * Custom URL scheme for deep linking (mobile/desktop apps)
         * Use '_deep' for standard deep links, null to disable, or custom scheme
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
         * Whether tracing is enabled
         */
        'enabled' => env('TRACING_ENABLED', true),
    ],
];