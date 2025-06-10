<?php

return [
    'seeders'        => [
        'basic'    => [
            'config'     => function (string $template, array $config): array {
                // Extract domain info from existing config
                $domain      = $config['domain'] ?? 'example.com';
                $apiUrl      = "https://$domain";
                $frontendUrl = 'https://' . str_replace('api.', '', $domain);

                // Core configuration
                $coreConfig = [
                    'app_name'     => $config['_seed_app_name'] ?? $config['app_name'] ?? 'QuVel',
                    'app_url'      => $apiUrl,
                    'frontend_url' => $frontendUrl,
                ];

                // Add mail configuration using seed parameters
                $coreConfig['mail_from_name'] = $config['_seed_mail_from_name']
                    ?? $config['mail_from_name']
                    ?? $coreConfig['app_name'] . ' Support';

                $coreConfig['mail_from_address'] = $config['_seed_mail_from_address']
                    ?? $config['mail_from_address']
                    ?? 'support@' . str_replace(['https://', 'http://', 'api.'], '', $domain);

                // Add capacitor scheme if provided
                if (isset($config['_seed_capacitor_scheme'])) {
                    $coreConfig['capacitor_scheme'] = $config['_seed_capacitor_scheme'];
                }

                return $coreConfig;
            },
            'visibility' => [
                'app_name'          => 'public',
                'app_url'           => 'public',
                'frontend_url'      => 'protected',
                'mail_from_name'    => 'private',
                'mail_from_address' => 'private',
                'capacitor_scheme'  => 'protected',
            ],
            'priority'   => 10, // Run very early
        ],
        'isolated' => [
            'config'     => function (string $template, array $config): array {
                // Extract domain info from existing config
                $domain      = $config['domain'] ?? 'example.com';
                $apiUrl      = "https://$domain";
                $frontendUrl = 'https://' . str_replace('api.', '', $domain);

                // Core configuration
                $coreConfig = [
                    'app_name'     => $config['_seed_app_name'] ?? $config['app_name'] ?? 'QuVel',
                    'app_url'      => $apiUrl,
                    'frontend_url' => $frontendUrl,
                ];

                // Add mail configuration using seed parameters
                $coreConfig['mail_from_name'] = $config['_seed_mail_from_name']
                    ?? $config['mail_from_name']
                    ?? $coreConfig['app_name'] . ' Support';

                $coreConfig['mail_from_address'] = $config['_seed_mail_from_address']
                    ?? $config['mail_from_address']
                    ?? 'support@' . str_replace(['https://', 'http://', 'api.'], '', $domain);

                // Add capacitor scheme if provided
                if (isset($config['_seed_capacitor_scheme'])) {
                    $coreConfig['capacitor_scheme'] = $config['_seed_capacitor_scheme'];
                }

                // Add internal API URL for isolated template
                if (!isset($config['internal_api_url'])) {
                    // Extract just the domain part for internal API
                    $internalDomain                 = str_replace(['https://', 'http://'], '', $apiUrl);
                    $coreConfig['internal_api_url'] = "http://{$internalDomain}:8000";
                }

                // Special handling for specific isolated domains (like the seeder does)
                if ($domain === 'api-lan') {
                    $coreConfig['internal_api_url'] = 'http://api-lan:8000';
                }

                return $coreConfig;
            },
            'visibility' => [
                'app_name'          => 'public',
                'app_url'           => 'public',
                'frontend_url'      => 'protected',
                'mail_from_name'    => 'private',
                'mail_from_address' => 'private',
                'capacitor_scheme'  => 'protected',
                'internal_api_url'  => 'protected',
            ],
            'priority'   => 10, // Run very early
        ],
    ],

    // Additional seeders for shared configs across all templates
    'shared_seeders' => [
        'recaptcha' => [
            'config'     => function (string $template, array $config): array {
                $recaptchaConfig = [];

                // Use seed parameters or environment variables
                if (isset($config['_seed_recaptcha_site_key'])) {
                    $recaptchaConfig['recaptcha_site_key']   = $config['_seed_recaptcha_site_key'];
                    $recaptchaConfig['recaptcha_secret_key'] = $config['_seed_recaptcha_secret_key'] ?? '';
                } elseif (env('RECAPTCHA_GOOGLE_SITE_KEY')) {
                    // Fallback to env for development
                    $recaptchaConfig['recaptcha_site_key']   = env('RECAPTCHA_GOOGLE_SITE_KEY');
                    $recaptchaConfig['recaptcha_secret_key'] = env('RECAPTCHA_GOOGLE_SECRET', '');
                }

                return $recaptchaConfig;
            },
            'visibility' => [
                'recaptcha_site_key'   => 'public',
                'recaptcha_secret_key' => 'private',
            ],
            'priority'   => 15,
        ],
        'pusher'    => [
            'config'     => function (string $template, array $config): array {
                $pusherConfig = [];

                // Use seed parameters or environment variables
                if (isset($config['_seed_pusher_app_key'])) {
                    $pusherConfig['pusher_app_key']     = $config['_seed_pusher_app_key'];
                    $pusherConfig['pusher_app_secret']  = $config['_seed_pusher_app_secret'] ?? '';
                    $pusherConfig['pusher_app_id']      = $config['_seed_pusher_app_id'] ?? '';
                    $pusherConfig['pusher_app_cluster'] = $config['_seed_pusher_app_cluster'] ?? 'mt1';
                } elseif (env('PUSHER_APP_KEY')) {
                    // Fallback to env for development
                    $pusherConfig['pusher_app_key']     = env('PUSHER_APP_KEY');
                    $pusherConfig['pusher_app_secret']  = env('PUSHER_APP_SECRET', '');
                    $pusherConfig['pusher_app_id']      = env('PUSHER_APP_ID', '');
                    $pusherConfig['pusher_app_cluster'] = env('PUSHER_APP_CLUSTER', 'mt1');
                }

                return $pusherConfig;
            },
            'visibility' => [
                'pusher_app_key'     => 'public',
                'pusher_app_secret'  => 'private',
                'pusher_app_id'      => 'private',
                'pusher_app_cluster' => 'public',
            ],
            'priority'   => 15,
        ],
    ],
];
