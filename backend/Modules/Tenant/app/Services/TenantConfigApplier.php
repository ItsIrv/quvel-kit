<?php

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Routing\UrlGenerator;
use Modules\Tenant\Models\Tenant;
use RuntimeException;

class TenantConfigApplier
{
    /**
     * Apply tenant-specific config at runtime.
     */
    public static function apply(Tenant $tenant): void
    {
        $config = $tenant->getEffectiveConfig();

        if (!$config) {
            throw new RuntimeException('Tenant config is missing.');
        }

        $app = app();
        /** @var Repository $appConfig */
        $appConfig = $app->make(Repository::class);

        // Backend - Core App
        $appConfig->set('app.name', $config->appName);
        $appConfig->set('app.env', $config->appEnv);
        $appConfig->set('app.key', $config->appKey);
        $appConfig->set('app.debug', $config->appDebug);
        $appConfig->set('app.url', $config->appUrl);

        // Frontend
        $appConfig->set('frontend.url', $config->frontendUrl);
        $appConfig->set('frontend.internal_api_url', $config->internalApiUrl);
        $appConfig->set('frontend.capacitor_scheme', $config->capacitorScheme);

        // Localization
        $appConfig->set('app.locale', $config->appLocale);
        $appConfig->set('app.fallback_locale', $config->appFallbackLocale);
        $appConfig->set('app.faker_locale', $config->appFakerLocale);

        // Logging
        $appConfig->set('logging.default', $config->logChannel);
        // $appConfig->set('logging.channels.stack.driver', $config->logStack);
        // $appConfig->set('logging.channels.stack.deprecations', $config->logDeprecationsChannel);
        $appConfig->set('logging.level', $config->logLevel);

        // Database
        $appConfig->set('database.default', $config->dbConnection);
        $appConfig->set('database.connections.mysql.host', $config->dbHost);
        $appConfig->set('database.connections.mysql.port', $config->dbPort);
        $appConfig->set('database.connections.mysql.database', $config->dbDatabase);
        $appConfig->set('database.connections.mysql.username', $config->dbUsername);
        $appConfig->set('database.connections.mysql.password', $config->dbPassword);

        // Session
        $appConfig->set('session.driver', $config->sessionDriver);
        $appConfig->set('session.lifetime', $config->sessionLifetime);
        $appConfig->set('session.encrypt', $config->sessionEncrypt);
        $appConfig->set('session.path', $config->sessionPath);
        $appHost = parse_url($config->appUrl, PHP_URL_HOST);

        if ($appHost) {
            $parts = explode('.', $appHost);

            if (count($parts) > 2) {
                array_shift($parts);
            }

            $sessionDomain = '.' . implode('.', $parts);
            $appConfig->set('session.domain', $sessionDomain);
        }

        // Cache
        $appConfig->set('cache.default', $config->cacheStore);
        $appConfig->set('cache.prefix', $config->cachePrefix);

        // Redis
        $appConfig->set('database.redis.client', $config->redisClient);
        $appConfig->set('database.redis.default.host', $config->redisHost);
        $appConfig->set('database.redis.default.password', $config->redisPassword);
        $appConfig->set('database.redis.default.port', $config->redisPort);

        // Mail
        $appConfig->set('mail.default', $config->mailMailer);
        $appConfig->set('mail.mailers.smtp.host', $config->mailHost);
        $appConfig->set('mail.mailers.smtp.port', $config->mailPort);
        $appConfig->set('mail.mailers.smtp.username', $config->mailUsername);
        $appConfig->set('mail.mailers.smtp.password', $config->mailPassword);
        $appConfig->set('mail.from.address', $config->mailFromAddress);
        $appConfig->set('mail.from.name', $config->mailFromName);

        // AWS
        $appConfig->set('filesystems.disks.s3.key', $config->awsAccessKeyId);
        $appConfig->set('filesystems.disks.s3.secret', $config->awsSecretAccessKey);
        $appConfig->set('filesystems.disks.s3.region', $config->awsDefaultRegion);
        $appConfig->set('filesystems.disks.s3.bucket', $config->awsBucket);
        $appConfig->set('filesystems.disks.s3.use_path_style_endpoint', $config->awsUsePathStyleEndpoint);

        // Socialite / OAuth
        $appConfig->set('auth.oauth.providers', $config->socialiteProviders);
        $appConfig->set('auth.oauth.nonce_ttl', $config->socialiteNonceTtl);
        $appConfig->set('auth.oauth.token_ttl', $config->socialiteTokenTtl);

        foreach (($config->oauthCredentials ?? []) as $provider => $credentials) {
            $appConfig->set("services.$provider.client_id", $credentials['client_id'] ?? '');
            $appConfig->set("services.$provider.client_secret", $credentials['client_secret'] ?? '');
            $appConfig->set(
                "services.$provider.redirect",
                "{$config->appUrl}/auth/provider/{$provider}/callback",
            );
        }

        // Pusher
        $appConfig->set('broadcasting.connections.pusher.key', $config->pusherAppKey);
        $appConfig->set('broadcasting.connections.pusher.secret', $config->pusherAppSecret);
        $appConfig->set('broadcasting.connections.pusher.app_id', $config->pusherAppId);
        $appConfig->set('broadcasting.connections.pusher.options.cluster', $config->pusherAppCluster);
        $appConfig->set('broadcasting.connections.pusher.options.port', $config->pusherPort);
        $appConfig->set('broadcasting.connections.pusher.options.scheme', $config->pusherScheme);

        // Internal
        $appConfig->set('quvel.default_password', $config->quvelDefaultPassword);
        $appConfig->set('quvel.api_domain', $config->quvelApiDomain);
        $appConfig->set('quvel.lan_domain', $config->quvelLanDomain);
        $appConfig->set('hmac_secret_key', $config->hmacSecretKey);

        $urlGenerator = app(UrlGenerator::class);
        $urlGenerator->forceRootUrl(config('app.url'));

        // TODO: Need a way for modules to register their own dynamic configs
        // all the way down to the seeder level
        $appConfig->set('auth.disable_socialite', $config->disableSocialite);
        $appConfig->set('auth.verify_email_before_login', $config->verifyEmailBeforeLogin);
    }
}
