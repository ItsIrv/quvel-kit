<?php

namespace Modules\Tenant\database\factories;

use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\ValueObjects\TenantConfig;

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
    ): array|TenantConfig {
        $apiUrl         = "https://$apiDomain";
        $frontendUrl    = 'https://' . str_replace('api.', '', $apiDomain);
        $internalApiUrl = $internalApiDomain ? "http://$internalApiDomain:8000" : null;

        // Create parameters array for TenantConfig
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

        // Create a new TenantConfig instance using the parameters array
        $config = new TenantConfig(...$params);

        return $toArray ? $config->toArray() : $config;
    }
}
