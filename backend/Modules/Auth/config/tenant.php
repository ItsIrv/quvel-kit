<?php

return [
    'seeders' => [
        'basic'    => [
            'config'     => [
                'session_cookie'      => 'quvel_session',
                'socialite_providers' => [],
                'oauth_credentials'   => [],
            ],
            'visibility' => [
                'session_cookie'      => 'protected',
                'socialite_providers' => 'public',
                'session_lifetime'    => 'protected',
            ],
            'priority'   => 20,
        ],
        'isolated' => [
            'config'     => function (string $template, array $config): array {
                // Generate unique session cookie for isolated templates
                $sessionCookie = 'quvel_session';
                if (isset($config['cache_prefix'])) {
                    // Extract just the unique ID part from cache_prefix (e.g., "tenant_68337c1aad007_" -> "68337c1aad007")
                    if (preg_match('/tenant_([a-z0-9]+)_?/i', $config['cache_prefix'], $matches)) {
                        $tenantId = $matches[1];
                        // Create a shorter, cleaner session cookie name
                        $sessionCookie = "quvel_{$tenantId}";
                    } else {
                        // Fallback to a simple unique session name
                        $sessionCookie = 'quvel_' . substr(md5($config['cache_prefix']), 0, 8);
                    }
                }

                return [
                    'session_cookie'      => $sessionCookie,
                    'socialite_providers' => ['google', 'microsoft'],
                    'oauth_credentials'   => [
                        'google'    => [
                            'client_id'     => env('GOOGLE_CLIENT_ID', 'your-google-client-id'),
                            'client_secret' => env('GOOGLE_CLIENT_SECRET', 'your-google-client-secret'),
                        ],
                        'microsoft' => [
                            'client_id'     => env('MICROSOFT_CLIENT_ID', 'your-microsoft-client-id'),
                            'client_secret' => env('MICROSOFT_CLIENT_SECRET', 'your-microsoft-client-secret'),
                        ],
                    ],
                    'session_lifetime'    => 240, // 4 hours for isolated tenants
                ];
            },
            'visibility' => [
                'session_cookie'      => 'protected',
                'socialite_providers' => 'public',
                'oauth_credentials'   => 'private',
                'session_lifetime'    => 'protected',
            ],
            'priority'   => 20,
        ],
    ],

    'pipes'   => [
        \Modules\Auth\Pipes\AuthConfigPipe::class,
    ],
];
