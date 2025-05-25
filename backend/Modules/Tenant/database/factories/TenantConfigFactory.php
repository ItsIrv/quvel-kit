<?php

namespace Modules\Tenant\database\factories;

use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

class TenantConfigFactory
{
    /**
     * Generate a tenant configuration.
     */
    public static function create(
        string $apiDomain,
        string $appName = 'QuVel',
        string $appEnv = 'local',
        string $mailFromName = 'QuVel Support',
        string $mailFromAddress = 'support@quvel.app',
        ?string $capacitorScheme = null,
        ?string $internalApiDomain = null,
        bool $toArray = true,
    ): array|DynamicTenantConfig {
        $apiUrl         = "https://$apiDomain";
        $frontendUrl    = 'https://' . str_replace('api.', '', $apiDomain);
        $internalApiUrl = $internalApiDomain ? "http://$internalApiDomain:8000" : null;

        // Create parameters array for DynamicTenantConfig
        $params = [
            // App Settings
            'appName'                 => $appName,
            'appEnv'                  => $appEnv,
            'appKey'                  => env('APP_KEY'),
            'appDebug'                => env('APP_DEBUG', true),
            'appTimezone'             => env('APP_TIMEZONE', 'UTC'),
            'appUrl'                  => $apiUrl,
            'frontendUrl'             => $frontendUrl,
            'internalApiUrl'          => $internalApiUrl,
            // Localization
            'appLocale'               => env('APP_LOCALE'),
            'appFallbackLocale'       => env('APP_FALLBACK_LOCALE'),
            // Logging
            'logChannel'              => env('LOG_CHANNEL', 'stack'),
            // 'logStack'                => env('LOG_STACK', 'single'),
            // 'logDeprecationsChannel'  => env('LOG_DEPRECATIONS_CHANNEL', null),
            'logLevel'                => env('LOG_LEVEL', 'debug'),
            // Database
            'dbConnection'            => env('DB_CONNECTION', 'mysql'),
            'dbHost'                  => env('DB_HOST', '127.0.0.1'),
            'dbPort'                  => 1,
            'dbDatabase'              => env('DB_DATABASE', 'quvel'),
            'dbUsername'              => env('DB_USERNAME', 'root'),
            'dbPassword'              => env('DB_PASSWORD', ''),
            // Session & Cache
            'sessionDriver'           => env('SESSION_DRIVER', 'file'),
            'sessionLifetime'         => env('SESSION_LIFETIME', 120),
            'sessionEncrypt'          => env('SESSION_ENCRYPT', false),
            'sessionPath'             => env('SESSION_PATH', '/'),
            'sessionDomain'           => env('SESSION_DOMAIN', ''),
            'sessionCookie'           => env('SESSION_COOKIE', 'quvel_session'),
            'cacheStore'              => env('CACHE_STORE', 'file'),
            'cachePrefix'             => env('CACHE_PREFIX', ''),
            // Redis
            'redisClient'             => env('REDIS_CLIENT', 'phpredis'),
            'redisHost'               => env('REDIS_HOST', '127.0.0.1'),
            'redisPassword'           => env('REDIS_PASSWORD', null),
            'redisPort'               => env('REDIS_PORT', 6379),
            // Mail
            'mailMailer'              => env('MAIL_MAILER', 'smtp'),
            'mailScheme'              => env('MAIL_SCHEME', null),
            'mailHost'                => env('MAIL_HOST', 'mailhog'),
            'mailPort'                => env('MAIL_PORT', 1025),
            'mailUsername'            => env('MAIL_USERNAME', null),
            'mailPassword'            => env('MAIL_PASSWORD', null),
            'mailFromAddress'         => $mailFromAddress,
            'mailFromName'            => $mailFromName,
            // AWS
            'awsAccessKeyId'          => env('AWS_ACCESS_KEY_ID', null),
            'awsSecretAccessKey'      => env('AWS_SECRET_ACCESS_KEY', null),
            'awsDefaultRegion'        => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'awsBucket'               => env('AWS_BUCKET', null),
            'awsUsePathStyleEndpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            // OAuth
            'socialiteProviders'      => ['google'],
            'socialiteNonceTtl'       => 60,
            'socialiteTokenTtl'       => 60,
            'oauthCredentials'        => [
                'google' => [
                    'client_id'     => env('GOOGLE_CLIENT_ID', null),
                    'client_secret' => env('GOOGLE_CLIENT_SECRET', null),
                ],
            ],
            // Pusher
            'pusherAppId'             => env('PUSHER_APP_ID', null),
            'pusherAppKey'            => env('PUSHER_APP_KEY', null),
            'pusherAppSecret'         => env('PUSHER_APP_SECRET', null),
            'pusherAppCluster'        => env('PUSHER_APP_CLUSTER', null),
            'pusherPort'              => env('PUSHER_PORT', null),
            'pusherScheme'            => env('PUSHER_SCHEME', null),
            // Internal
            'hmacSecretKey'           => env('HMAC_SECRET_KEY', 'hmac_secret_key_here'),
            'disableSocialite'        => env('DISABLE_SOCIALITE', false),
            'verifyEmailBeforeLogin'  => env('VERIFY_EMAIL_BEFORE_LOGIN', true),
            'capacitorScheme'         => $capacitorScheme,
            'recaptchaGoogleSecret'   => env('RECAPTCHA_GOOGLE_SECRET', null),
            'recaptchaGoogleSiteKey'  => env('RECAPTCHA_GOOGLE_SITE_KEY', null),
            // Visibility
            'visibility'              => [
                'internalApiUrl'         => TenantConfigVisibility::PROTECTED ,
                'frontendUrl'            => TenantConfigVisibility::PROTECTED ,
                'sessionCookie'          => TenantConfigVisibility::PROTECTED ,
                'appUrl'                 => TenantConfigVisibility::PUBLIC ,
                'appName'                => TenantConfigVisibility::PUBLIC ,
                'pusherAppKey'           => TenantConfigVisibility::PUBLIC ,
                'pusherAppCluster'       => TenantConfigVisibility::PUBLIC ,
                'socialiteProviders'     => TenantConfigVisibility::PUBLIC ,
                'recaptchaGoogleSiteKey' => TenantConfigVisibility::PUBLIC ,
            ],
        ];

        // Create a new DynamicTenantConfig instance
        $config = new DynamicTenantConfig([
            'app_name'                    => $appName,
            'app_env'                     => $appEnv,
            'app_key'                     => env('APP_KEY'),
            'app_debug'                   => env('APP_DEBUG', true),
            'app_timezone'                => env('APP_TIMEZONE', 'UTC'),
            'app_url'                     => $apiUrl,
            'frontend_url'                => $frontendUrl,
            'internal_api_url'            => $internalApiUrl,
            'app_locale'                  => env('APP_LOCALE'),
            'app_fallback_locale'         => env('APP_FALLBACK_LOCALE'),
            'log_channel'                 => env('LOG_CHANNEL', 'stack'),
            'log_level'                   => env('LOG_LEVEL', 'debug'),
            'db_connection'               => env('DB_CONNECTION', 'mysql'),
            'db_host'                     => env('DB_HOST', '127.0.0.1'),
            'db_port'                     => 1,
            'db_database'                 => env('DB_DATABASE', 'quvel'),
            'db_username'                 => env('DB_USERNAME', 'root'),
            'db_password'                 => env('DB_PASSWORD', ''),
            'session_driver'              => env('SESSION_DRIVER', 'file'),
            'session_lifetime'            => env('SESSION_LIFETIME', 120),
            'session_encrypt'             => env('SESSION_ENCRYPT', false),
            'session_path'                => env('SESSION_PATH', '/'),
            'session_domain'              => env('SESSION_DOMAIN', ''),
            'session_cookie'              => env('SESSION_COOKIE', 'quvel_session'),
            'cache_store'                 => env('CACHE_STORE', 'file'),
            'cache_prefix'                => env('CACHE_PREFIX', ''),
            'redis_client'                => env('REDIS_CLIENT', 'phpredis'),
            'redis_host'                  => env('REDIS_HOST', '127.0.0.1'),
            'redis_password'              => env('REDIS_PASSWORD', null),
            'redis_port'                  => env('REDIS_PORT', 6379),
            'mail_mailer'                 => env('MAIL_MAILER', 'smtp'),
            'mail_scheme'                 => env('MAIL_SCHEME', null),
            'mail_host'                   => env('MAIL_HOST', 'mailhog'),
            'mail_port'                   => env('MAIL_PORT', 1025),
            'mail_username'               => env('MAIL_USERNAME', null),
            'mail_password'               => env('MAIL_PASSWORD', null),
            'mail_from_address'           => $mailFromAddress,
            'mail_from_name'              => $mailFromName,
            'aws_access_key_id'           => env('AWS_ACCESS_KEY_ID', null),
            'aws_secret_access_key'       => env('AWS_SECRET_ACCESS_KEY', null),
            'aws_default_region'          => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'aws_bucket'                  => env('AWS_BUCKET', null),
            'aws_use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'socialite_providers'         => ['google'],
            'socialite_nonce_ttl'         => 60,
            'socialite_token_ttl'         => 60,
            'oauth_credentials'           => [
                'google' => [
                    'client_id'     => env('GOOGLE_CLIENT_ID', null),
                    'client_secret' => env('GOOGLE_CLIENT_SECRET', null),
                ],
            ],
            'pusher_app_id'               => env('PUSHER_APP_ID', null),
            'pusher_app_key'              => env('PUSHER_APP_KEY', null),
            'pusher_app_secret'           => env('PUSHER_APP_SECRET', null),
            'pusher_app_cluster'          => env('PUSHER_APP_CLUSTER', null),
            'pusher_port'                 => env('PUSHER_PORT', null),
            'pusher_scheme'               => env('PUSHER_SCHEME', null),
            'hmac_secret_key'             => env('HMAC_SECRET_KEY', 'hmac_secret_key_here'),
            'disable_socialite'           => env('DISABLE_SOCIALITE', false),
            'verify_email_before_login'   => env('VERIFY_EMAIL_BEFORE_LOGIN', true),
            'capacitor_scheme'            => $capacitorScheme,
            'recaptcha_google_secret'     => env('RECAPTCHA_GOOGLE_SECRET', null),
            'recaptcha_google_site_key'   => env('RECAPTCHA_GOOGLE_SITE_KEY', null),
        ]);

        // Set visibility for config keys
        $config->setVisibility('internal_api_url', TenantConfigVisibility::PROTECTED);
        $config->setVisibility('frontend_url', TenantConfigVisibility::PROTECTED);
        $config->setVisibility('session_cookie', TenantConfigVisibility::PROTECTED);
        $config->setVisibility('app_url', TenantConfigVisibility::PUBLIC);
        $config->setVisibility('app_name', TenantConfigVisibility::PUBLIC);
        $config->setVisibility('pusher_app_key', TenantConfigVisibility::PUBLIC);
        $config->setVisibility('pusher_app_cluster', TenantConfigVisibility::PUBLIC);
        $config->setVisibility('socialite_providers', TenantConfigVisibility::PUBLIC);
        $config->setVisibility('recaptcha_google_site_key', TenantConfigVisibility::PUBLIC);

        return $toArray ? $config->toArray() : $config;
    }
}
