<?php

namespace Modules\Tenant\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use Modules\Tenant\Enums\TenantConfigVisibility;

/**
 * Represents the configuration for a tenant.
 *
 * TODO: Key management TBD
 *
 * @implements Arrayable<string, mixed>
 */
class TenantConfig implements Arrayable
{
    // App Settings
    public readonly string $appName;
    public readonly string $appEnv;
    public readonly string $appKey;
    public readonly bool $appDebug;
    public readonly string $appTimezone;
    public readonly string $appUrl;

    // Frontend
    public readonly ?string $frontendUrl;
    public readonly ?string $internalApiUrl;
    public readonly ?string $capacitorScheme;

    // Localization
    public readonly ?string $appLocale;
    public readonly ?string $appFallbackLocale;
    public readonly ?string $appFakerLocale;

    // Logging
    public readonly ?string $logChannel;
    public readonly ?string $logStack;
    public readonly ?string $logDeprecationsChannel;
    public readonly ?string $logLevel;

    // Database
    public readonly ?string $dbConnection;
    public readonly ?string $dbHost;
    public readonly ?int $dbPort;
    public readonly ?string $dbDatabase;
    public readonly ?string $dbUsername;
    public readonly ?string $dbPassword;

    // Session & Cache
    public readonly ?string $sessionDriver;
    public readonly ?int $sessionLifetime;
    public readonly ?bool $sessionEncrypt;
    public readonly ?string $sessionPath;
    public readonly ?string $sessionDomain;
    public readonly ?string $cacheStore;
    public readonly ?string $cachePrefix;

    // Redis
    public readonly ?string $redisClient;
    public readonly ?string $redisHost;
    public readonly ?string $redisPassword;
    public readonly ?int $redisPort;

    // Mail
    public readonly ?string $mailMailer;
    public readonly ?string $mailScheme;
    public readonly ?string $mailHost;
    public readonly ?int $mailPort;
    public readonly ?string $mailUsername;
    public readonly ?string $mailPassword;
    public readonly string $mailFromAddress;
    public readonly string $mailFromName;

    // AWS
    public readonly ?string $awsAccessKeyId;
    public readonly ?string $awsSecretAccessKey;
    public readonly ?string $awsDefaultRegion;
    public readonly ?string $awsBucket;
    public readonly ?bool $awsUsePathStyleEndpoint;

    // OAuth / Socialite
    public readonly ?array $socialiteProviders;
    public readonly ?int $socialiteNonceTtl;
    public readonly ?int $socialiteTokenTtl;
    public readonly ?array $oauthCredentials;

    // Pusher
    public readonly ?string $pusherAppId;
    public readonly ?string $pusherAppKey;
    public readonly ?string $pusherAppSecret;
    public readonly ?string $pusherAppCluster;
    public readonly ?int $pusherPort;
    public readonly ?string $pusherScheme;

    // Internal/QuVel Specific
    public readonly ?string $quvelDefaultPassword;
    public readonly ?string $quvelApiDomain;
    public readonly ?string $quvelLanDomain;
    public readonly ?string $hmacSecretKey;

    // Frontend visibility control
    public readonly array $visibility;

    /**
     * Constructor
     */
    public function __construct(
        // App Settings
        string $appName,
        string $appEnv,
        string $appKey,
        bool $appDebug,
        string $appTimezone,
        string $appUrl,
        // Frontend
        string $frontendUrl,
        ?string $internalApiUrl,
        ?string $capacitorScheme,
        // Localization
        ?string $appLocale,
        ?string $appFallbackLocale,
        ?string $appFakerLocale,
        // Logging
        ?string $logChannel,
        ?string $logStack,
        ?string $logDeprecationsChannel,
        ?string $logLevel,
        // Database
        ?string $dbConnection,
        ?string $dbHost,
        ?int $dbPort,
        ?string $dbDatabase,
        ?string $dbUsername,
        ?string $dbPassword,
        // Session & Cache
        ?string $sessionDriver,
        ?int $sessionLifetime,
        ?bool $sessionEncrypt,
        ?string $sessionPath,
        ?string $sessionDomain,
        ?string $cacheStore,
        ?string $cachePrefix,
        // Redis
        ?string $redisClient,
        ?string $redisHost,
        ?string $redisPassword,
        ?int $redisPort,
        // Mail
        ?string $mailMailer,
        ?string $mailScheme,
        string $mailHost,
        ?int $mailPort,
        ?string $mailUsername,
        ?string $mailPassword,
        string $mailFromAddress,
        string $mailFromName,
        // AWS
        ?string $awsAccessKeyId,
        ?string $awsSecretAccessKey,
        ?string $awsDefaultRegion,
        ?string $awsBucket,
        ?bool $awsUsePathStyleEndpoint,
        // OAuth
        ?array $socialiteProviders,
        ?int $socialiteNonceTtl,
        ?int $socialiteTokenTtl,
        ?array $oauthCredentials,
        // Pusher
        ?string $pusherAppId,
        ?string $pusherAppKey,
        ?string $pusherAppSecret,
        ?string $pusherAppCluster,
        ?int $pusherPort,
        ?string $pusherScheme,
        // Internal
        ?string $quvelDefaultPassword,
        ?string $quvelApiDomain,
        ?string $quvelLanDomain,
        ?string $hmacSecretKey,
        // Visibility
        ?array $visibility = [],
    ) {
        $this->appName     = $appName;
        $this->appEnv      = $appEnv;
        $this->appKey      = $appKey;
        $this->appDebug    = $appDebug;
        $this->appTimezone = $appTimezone;
        $this->appUrl      = $appUrl;

        $this->capacitorScheme = $capacitorScheme;
        $this->frontendUrl     = $frontendUrl;
        $this->internalApiUrl  = $internalApiUrl;

        $this->appLocale         = $appLocale;
        $this->appFallbackLocale = $appFallbackLocale;
        $this->appFakerLocale    = $appFakerLocale;

        $this->logChannel             = $logChannel;
        $this->logStack               = $logStack;
        $this->logDeprecationsChannel = $logDeprecationsChannel;
        $this->logLevel               = $logLevel;

        $this->dbConnection = $dbConnection;
        $this->dbHost       = $dbHost;
        $this->dbPort       = $dbPort;
        $this->dbDatabase   = $dbDatabase;
        $this->dbUsername   = $dbUsername;
        $this->dbPassword   = $dbPassword;

        $this->sessionDriver   = $sessionDriver;
        $this->sessionLifetime = $sessionLifetime;
        $this->sessionEncrypt  = $sessionEncrypt;
        $this->sessionPath     = $sessionPath;
        $this->sessionDomain   = $sessionDomain;
        $this->cacheStore      = $cacheStore;
        $this->cachePrefix     = $cachePrefix;

        $this->redisClient   = $redisClient;
        $this->redisHost     = $redisHost;
        $this->redisPassword = $redisPassword;
        $this->redisPort     = $redisPort;

        $this->mailMailer      = $mailMailer;
        $this->mailScheme      = $mailScheme;
        $this->mailHost        = $mailHost;
        $this->mailPort        = $mailPort;
        $this->mailUsername    = $mailUsername;
        $this->mailPassword    = $mailPassword;
        $this->mailFromAddress = $mailFromAddress;
        $this->mailFromName    = $mailFromName;

        $this->awsAccessKeyId          = $awsAccessKeyId;
        $this->awsSecretAccessKey      = $awsSecretAccessKey;
        $this->awsDefaultRegion        = $awsDefaultRegion;
        $this->awsBucket               = $awsBucket;
        $this->awsUsePathStyleEndpoint = $awsUsePathStyleEndpoint;

        $this->socialiteProviders = $socialiteProviders;
        $this->socialiteNonceTtl  = $socialiteNonceTtl;
        $this->socialiteTokenTtl  = $socialiteTokenTtl;
        $this->oauthCredentials   = $oauthCredentials;

        $this->pusherAppId      = $pusherAppId;
        $this->pusherAppKey     = $pusherAppKey;
        $this->pusherAppSecret  = $pusherAppSecret;
        $this->pusherAppCluster = $pusherAppCluster;
        $this->pusherPort       = $pusherPort;
        $this->pusherScheme     = $pusherScheme;

        $this->quvelDefaultPassword = $quvelDefaultPassword;
        $this->quvelApiDomain       = $quvelApiDomain;
        $this->quvelLanDomain       = $quvelLanDomain;
        $this->hmacSecretKey        = $hmacSecretKey;

        $this->visibility = $visibility;
    }

    public static function fromArray(array $data): self
    {
        $params = [
            // App Settings
            'appName'                 => $data['app_name'] ?? '',
            'appEnv'                  => $data['app_env'] ?? 'local',
            'appKey'                  => $data['app_key'] ?? '',
            'appDebug'                => (bool) ($data['app_debug'] ?? false),
            'appTimezone'             => $data['app_timezone'] ?? 'UTC',
            'appUrl'                  => $data['app_url'] ?? '',

            // Frontend
            'frontendUrl'             => $data['frontend_url'] ?? null,
            'capacitorScheme'         => $data['capacitor_scheme'] ?? null,
            'internalApiUrl'          => $data['internal_api_url'] ?? null,

            // Localization
            'appLocale'               => $data['app_locale'] ?? 'en',
            'appFallbackLocale'       => $data['app_fallback_locale'] ?? 'en',
            'appFakerLocale'          => $data['app_faker_locale'] ?? 'en_US',

            // Logging
            'logChannel'              => $data['log_channel'] ?? 'stack',
            'logStack'                => $data['log_stack'] ?? 'single',
            'logDeprecationsChannel'  => $data['log_deprecations_channel'] ?? null,
            'logLevel'                => $data['log_level'] ?? 'debug',

            // Database
            'dbConnection'            => $data['db_connection'] ?? 'mysql',
            'dbHost'                  => $data['db_host'] ?? '127.0.0.1',
            'dbPort'                  => (int) ($data['db_port'] ?? 3306),
            'dbDatabase'              => $data['db_database'] ?? '',
            'dbUsername'              => $data['db_username'] ?? '',
            'dbPassword'              => $data['db_password'] ?? '',

            // Session & Cache
            'sessionDriver'           => $data['session_driver'] ?? 'file',
            'sessionLifetime'         => (int) ($data['session_lifetime'] ?? 120),
            'sessionEncrypt'          => (bool) ($data['session_encrypt'] ?? false),
            'sessionPath'             => $data['session_path'] ?? '/',
            'sessionDomain'           => $data['session_domain'] ?? '',
            'cacheStore'              => $data['cache_store'] ?? 'file',
            'cachePrefix'             => $data['cache_prefix'] ?? '',

            // Redis
            'redisClient'             => $data['redis_client'] ?? 'phpredis',
            'redisHost'               => $data['redis_host'] ?? '127.0.0.1',
            'redisPassword'           => $data['redis_password'] ?? null,
            'redisPort'               => (int) ($data['redis_port'] ?? 6379),

            // Mail
            'mailMailer'              => $data['mail_mailer'] ?? 'smtp',
            'mailScheme'              => $data['mail_scheme'] ?? null,
            'mailHost'                => $data['mail_host'] ?? '',
            'mailPort'                => (int) ($data['mail_port'] ?? 2525),
            'mailUsername'            => $data['mail_username'] ?? null,
            'mailPassword'            => $data['mail_password'] ?? null,
            'mailFromAddress'         => $data['mail_from_address'] ?? '',
            'mailFromName'            => $data['mail_from_name'] ?? '',

            // AWS
            'awsAccessKeyId'          => $data['aws_access_key_id'] ?? null,
            'awsSecretAccessKey'      => $data['aws_secret_access_key'] ?? null,
            'awsDefaultRegion'        => $data['aws_default_region'] ?? 'us-east-1',
            'awsBucket'               => $data['aws_bucket'] ?? null,
            'awsUsePathStyleEndpoint' => (bool) ($data['aws_use_path_style_endpoint'] ?? false),

            // OAuth
            'socialiteProviders'      => $data['socialite_providers'] ?? [],
            'socialiteNonceTtl'       => (int) ($data['socialite_nonce_ttl'] ?? 60),
            'socialiteTokenTtl'       => (int) ($data['socialite_token_ttl'] ?? 60),
            'oauthCredentials'        => $data['oauth_credentials'] ?? [],

            // Pusher
            'pusherAppId'             => $data['pusher_app_id'] ?? null,
            'pusherAppKey'            => $data['pusher_app_key'] ?? null,
            'pusherAppSecret'         => $data['pusher_app_secret'] ?? null,
            'pusherAppCluster'        => $data['pusher_app_cluster'] ?? null,
            'pusherPort'              => isset($data['pusher_port']) ? (int) $data['pusher_port'] : null,
            'pusherScheme'            => $data['pusher_scheme'] ?? null,

            // Internal
            'quvelDefaultPassword'    => $data['quvel_default_password'] ?? null,
            'quvelApiDomain'          => $data['quvel_api_domain'] ?? null,
            'quvelLanDomain'          => $data['quvel_lan_domain'] ?? null,
            'hmacSecretKey'           => $data['hmac_secret_key'] ?? null,

            // Visibility
            'visibility'              => array_map(
                static fn ($v): TenantConfigVisibility => TenantConfigVisibility::tryFrom($v) ?? TenantConfigVisibility::PRIVATE ,
                $data['__visibility'] ?? [],
            ),
        ];

        // Create and return a new instance using the extracted parameters
        return new self(...array_values($params));
    }

    public function toArray(): array
    {
        return [
            // App Settings
            'app_name'                    => $this->appName,
            'app_env'                     => $this->appEnv,
            'app_key'                     => $this->appKey,
            'app_debug'                   => $this->appDebug,
            'app_timezone'                => $this->appTimezone,
            'app_url'                     => $this->appUrl,

            // Frontend
            'frontend_url'                => $this->frontendUrl,
            'internal_api_url'            => $this->internalApiUrl,
            'capacitor_scheme'            => $this->capacitorScheme,

            // Localization
            'app_locale'                  => $this->appLocale,
            'app_fallback_locale'         => $this->appFallbackLocale,
            'app_faker_locale'            => $this->appFakerLocale,

            // Logging
            'log_channel'                 => $this->logChannel,
            'log_stack'                   => $this->logStack,
            'log_deprecations_channel'    => $this->logDeprecationsChannel,
            'log_level'                   => $this->logLevel,

            // Database
            'db_connection'               => $this->dbConnection,
            'db_host'                     => $this->dbHost,
            'db_port'                     => $this->dbPort,
            'db_database'                 => $this->dbDatabase,
            'db_username'                 => $this->dbUsername,
            'db_password'                 => $this->dbPassword,

            // Session & Cache
            'session_driver'              => $this->sessionDriver,
            'session_lifetime'            => $this->sessionLifetime,
            'session_encrypt'             => $this->sessionEncrypt,
            'session_path'                => $this->sessionPath,
            'session_domain'              => $this->sessionDomain,
            'cache_store'                 => $this->cacheStore,
            'cache_prefix'                => $this->cachePrefix,

            // Redis
            'redis_client'                => $this->redisClient,
            'redis_host'                  => $this->redisHost,
            'redis_password'              => $this->redisPassword,
            'redis_port'                  => $this->redisPort,

            // Mail
            'mail_mailer'                 => $this->mailMailer,
            'mail_scheme'                 => $this->mailScheme,
            'mail_host'                   => $this->mailHost,
            'mail_port'                   => $this->mailPort,
            'mail_username'               => $this->mailUsername,
            'mail_password'               => $this->mailPassword,
            'mail_from_address'           => $this->mailFromAddress,
            'mail_from_name'              => $this->mailFromName,

            // AWS
            'aws_access_key_id'           => $this->awsAccessKeyId,
            'aws_secret_access_key'       => $this->awsSecretAccessKey,
            'aws_default_region'          => $this->awsDefaultRegion,
            'aws_bucket'                  => $this->awsBucket,
            'aws_use_path_style_endpoint' => $this->awsUsePathStyleEndpoint,

            // OAuth
            'socialite_providers'         => $this->socialiteProviders,
            'socialite_nonce_ttl'         => $this->socialiteNonceTtl,
            'socialite_token_ttl'         => $this->socialiteTokenTtl,
            'oauth_credentials'           => $this->oauthCredentials,

            // Pusher
            'pusher_app_id'               => $this->pusherAppId,
            'pusher_app_key'              => $this->pusherAppKey,
            'pusher_app_secret'           => $this->pusherAppSecret,
            'pusher_app_cluster'          => $this->pusherAppCluster,
            'pusher_port'                 => $this->pusherPort,
            'pusher_scheme'               => $this->pusherScheme,

            // Internal/QuVel
            'quvel_default_password'      => $this->quvelDefaultPassword,
            'quvel_api_domain'            => $this->quvelApiDomain,
            'quvel_lan_domain'            => $this->quvelLanDomain,
            'hmac_secret_key'             => $this->hmacSecretKey,

            // Visibility
            '__visibility'                => array_map(
                static fn (TenantConfigVisibility $v) => $v->value,
                $this->visibility,
            ),
        ];
    }
}
