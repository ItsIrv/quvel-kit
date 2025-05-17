<?php

namespace Modules\Tenant\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Context;
use Modules\Tenant\Models\Tenant;
use RuntimeException;

use function app;

class ConfigApplier
{
    /**
     * Apply tenant-specific config at runtime.
     */
    public static function apply(Tenant $tenant, ConfigRepository $appConfig): void
    {
        $config = $tenant->getEffectiveConfig();

        if (!$config) {
            throw new RuntimeException('Tenant config is missing.');
        }

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
        $appConfig->set('session.domain', $config->sessionDomain);
        $appConfig->set('session.cookie', $config->sessionCookie);

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
        $appConfig->set('auth.socialite.providers', $config->socialiteProviders);
        $appConfig->set('auth.socialite.nonce_ttl', $config->socialiteNonceTtl);
        $appConfig->set('auth.socialite.token_ttl', $config->socialiteTokenTtl);
        $appConfig->set('auth.socialite.hmac_secret', $config->hmacSecretKey);

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

        // CORS
        $appConfig->set('cors.allowed_origins', [
            $config->appUrl,
            $config->frontendUrl,
        ]);

        // URLs
        $urlGenerator = app(UrlGenerator::class);
        $urlGenerator->forceRootUrl($config->appUrl);

        // TODO: Need a way for modules to register their own dynamic configs
        // all the way down to the seeder level up to dynamic runtime
        $appConfig->set('auth.disable_socialite', $config->disableSocialite);
        $appConfig->set('auth.verify_email_before_login', $config->verifyEmailBeforeLogin);
        $appConfig->set('core.recaptcha.google.secret', $config->recaptchaGoogleSecret);

        // Laravel Context
        Context::add('tenant_id', $tenant->public_id);
    }
}
