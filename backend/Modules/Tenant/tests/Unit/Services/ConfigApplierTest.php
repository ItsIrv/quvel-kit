<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Context;
use Mockery;
use Mockery\MockInterface;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\ConfigApplier;
use Modules\Tenant\ValueObjects\TenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use RuntimeException;
use Tests\TestCase;

#[CoversClass(ConfigApplier::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
final class ConfigApplierTest extends TestCase
{
    /**
     * @var Tenant|MockInterface
     */
    protected Tenant $tenant;

    /**
     * @var ConfigRepository|MockInterface
     */
    protected ConfigRepository $config;

    /**
     * @var TenantConfig|MockInterface
     */
    protected TenantConfig $tenantConfig;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant       = Mockery::mock(Tenant::class);
        $this->config       = Mockery::mock(ConfigRepository::class);
        $this->tenantConfig = Mockery::mock(TenantConfig::class);
    }

    #[TestDox('It should apply tenant config to app config')]
    public function testAppliesTenantConfigToAppConfig(): void
    {
        // Arrange
        // Create a TenantConfig instance with all required properties
        $tenantConfig = new TenantConfig(
            appUrl: 'https://test.example.com',
            frontendUrl: 'https://app.test.example.com',
            internalApiUrl: 'https://internal-api.test.example.com',
            appDebug: true,
            appTimezone: 'UTC',
            appKey: 'test-key',
            appName: 'Test Tenant',
            appEnv: 'testing',
            appLocale: 'en',
            appFallbackLocale: 'en',
            logChannel: 'stack',
            logLevel: 'debug',
            dbConnection: 'mysql',
            dbHost: 'localhost',
            dbPort: 3306,
            dbDatabase: 'test_db',
            dbUsername: 'test_user',
            dbPassword: 'test_password',
            sessionDriver: 'file',
            sessionLifetime: 120,
            sessionEncrypt: false,
            sessionPath: '/',
            sessionDomain: 'test.example.com',
            sessionCookie: 'test_session',
            cacheStore: 'file',
            cachePrefix: 'test_',
            redisClient: 'phpredis',
            redisHost: 'localhost',
            redisPassword: null,
            redisPort: 6379,
            mailMailer: 'smtp',
            mailScheme: null,
            mailHost: 'smtp.example.com',
            mailPort: 587,
            mailUsername: 'test@example.com',
            mailPassword: 'mail_password',
            mailFromAddress: 'no-reply@example.com',
            mailFromName: 'Test App',
            capacitorScheme: 'test-scheme',
            awsAccessKeyId: 'aws_key',
            awsSecretAccessKey: 'aws_secret',
            awsDefaultRegion: 'us-west-2',
            awsBucket: 'test-bucket',
            awsUsePathStyleEndpoint: true,
            socialiteProviders: ['github', 'google'],
            socialiteNonceTtl: 3600,
            socialiteTokenTtl: 86400,
            pusherAppId: 'pusher_app_id',
            pusherAppKey: 'pusher_app_key',
            pusherAppSecret: 'pusher_app_secret',
            pusherAppCluster: 'pusher_app_cluster',
            pusherPort: 443,
            pusherScheme: 'https',
            hmacSecretKey: 'hmac_secret',
            recaptchaGoogleSecret: 'recaptcha_google_secret',
            disableSocialite: false,
            verifyEmailBeforeLogin: true,
            oauthCredentials: [
                'github' => [
                    'client_id'     => 'github_client_id',
                    'client_secret' => 'github_client_secret',
                ],
                'google' => [
                    'client_id'     => 'google_client_id',
                    'client_secret' => 'google_client_secret',
                ],
            ],
        );

        // Replace the mocked tenantConfig with the real instance
        $this->tenantConfig = $tenantConfig;

        $this->tenant->shouldReceive('getEffectiveConfig')
            ->once()
            ->andReturn($this->tenantConfig);

        $this->tenant->shouldReceive('getAttribute')
            ->twice()
            ->with('public_id')
            ->andReturn('test-tenant-id');

        // Expect config settings to be applied
        // Core App Settings
        $this->config->shouldReceive('set')->with('app.name', $this->tenantConfig->appName)->once();
        $this->config->shouldReceive('set')->with('app.env', $this->tenantConfig->appEnv)->once();
        $this->config->shouldReceive('set')->with('app.key', $this->tenantConfig->appKey)->once();
        $this->config->shouldReceive('set')->with('app.debug', $this->tenantConfig->appDebug)->once();
        $this->config->shouldReceive('set')->with('app.url', $this->tenantConfig->appUrl)->once();

        // Frontend Settings
        $this->config->shouldReceive('set')->with('frontend.url', $this->tenantConfig->frontendUrl)->once();
        $this->config->shouldReceive('set')->with('frontend.internal_api_url', $this->tenantConfig->internalApiUrl)->once();
        $this->config->shouldReceive('set')->with('frontend.capacitor_scheme', $this->tenantConfig->capacitorScheme)->once();

        // Localization Settings
        $this->config->shouldReceive('set')->with('app.locale', $this->tenantConfig->appLocale)->once();
        $this->config->shouldReceive('set')->with('app.fallback_locale', $this->tenantConfig->appFallbackLocale)->once();

        // Logging Settings
        $this->config->shouldReceive('set')->with('logging.default', $this->tenantConfig->logChannel)->once();
        $this->config->shouldReceive('set')->with('logging.level', $this->tenantConfig->logLevel)->once();

        // Database Settings
        $this->config->shouldReceive('set')->with('database.default', $this->tenantConfig->dbConnection)->once();
        $this->config->shouldReceive('set')->with('database.connections.mysql.host', $this->tenantConfig->dbHost)->once();
        $this->config->shouldReceive('set')->with('database.connections.mysql.port', $this->tenantConfig->dbPort)->once();
        $this->config->shouldReceive('set')->with('database.connections.mysql.database', $this->tenantConfig->dbDatabase)->once();
        $this->config->shouldReceive('set')->with('database.connections.mysql.username', $this->tenantConfig->dbUsername)->once();
        $this->config->shouldReceive('set')->with('database.connections.mysql.password', $this->tenantConfig->dbPassword)->once();

        // Session Settings
        $this->config->shouldReceive('set')->with('session.driver', $this->tenantConfig->sessionDriver)->once();
        $this->config->shouldReceive('set')->with('session.lifetime', $this->tenantConfig->sessionLifetime)->once();
        $this->config->shouldReceive('set')->with('session.encrypt', $this->tenantConfig->sessionEncrypt)->once();
        $this->config->shouldReceive('set')->with('session.path', $this->tenantConfig->sessionPath)->once();
        $this->config->shouldReceive('set')->with('session.domain', $this->tenantConfig->sessionDomain)->once();
        $this->config->shouldReceive('set')->with('session.cookie', $this->tenantConfig->sessionCookie)->once();

        // Cache Settings
        $this->config->shouldReceive('set')->with('cache.default', $this->tenantConfig->cacheStore)->once();
        $this->config->shouldReceive('set')->with('cache.prefix', $this->tenantConfig->cachePrefix)->once();

        // Redis Settings
        $this->config->shouldReceive('set')->with('database.redis.client', $this->tenantConfig->redisClient)->once();
        $this->config->shouldReceive('set')->with('database.redis.default.host', $this->tenantConfig->redisHost)->once();
        $this->config->shouldReceive('set')->with('database.redis.default.password', $this->tenantConfig->redisPassword)->once();
        $this->config->shouldReceive('set')->with('database.redis.default.port', $this->tenantConfig->redisPort)->once();

        // Mail Settings
        $this->config->shouldReceive('set')->with('mail.default', $this->tenantConfig->mailMailer)->once();
        $this->config->shouldReceive('set')->with('mail.mailers.smtp.host', $this->tenantConfig->mailHost)->once();
        $this->config->shouldReceive('set')->with('mail.mailers.smtp.port', $this->tenantConfig->mailPort)->once();
        $this->config->shouldReceive('set')->with('mail.mailers.smtp.username', $this->tenantConfig->mailUsername)->once();
        $this->config->shouldReceive('set')->with('mail.mailers.smtp.password', $this->tenantConfig->mailPassword)->once();
        $this->config->shouldReceive('set')->with('mail.from.address', $this->tenantConfig->mailFromAddress)->once();
        $this->config->shouldReceive('set')->with('mail.from.name', $this->tenantConfig->mailFromName)->once();

        // AWS Settings
        $this->config->shouldReceive('set')->with('filesystems.disks.s3.key', $this->tenantConfig->awsAccessKeyId)->once();
        $this->config->shouldReceive('set')->with('filesystems.disks.s3.secret', $this->tenantConfig->awsSecretAccessKey)->once();
        $this->config->shouldReceive('set')->with('filesystems.disks.s3.region', $this->tenantConfig->awsDefaultRegion)->once();
        $this->config->shouldReceive('set')->with('filesystems.disks.s3.bucket', $this->tenantConfig->awsBucket)->once();
        $this->config->shouldReceive('set')->with('filesystems.disks.s3.use_path_style_endpoint', $this->tenantConfig->awsUsePathStyleEndpoint)->once();

        // Socialite / OAuth Settings
        $this->config->shouldReceive('set')->with('auth.socialite.providers', $this->tenantConfig->socialiteProviders)->once();
        $this->config->shouldReceive('set')->with('auth.socialite.nonce_ttl', $this->tenantConfig->socialiteNonceTtl)->once();
        $this->config->shouldReceive('set')->with('auth.socialite.token_ttl', $this->tenantConfig->socialiteTokenTtl)->once();
        $this->config->shouldReceive('set')->with('auth.socialite.hmac_secret', $this->tenantConfig->hmacSecretKey)->once();

        // OAuth Provider Settings
        $this->config->shouldReceive('set')->with('services.github.client_id', 'github_client_id')->once();
        $this->config->shouldReceive('set')->with('services.github.client_secret', 'github_client_secret')->once();
        $this->config->shouldReceive('set')->with('services.github.redirect', 'https://test.example.com/auth/provider/github/callback')->once();
        $this->config->shouldReceive('set')->with('services.google.client_id', 'google_client_id')->once();
        $this->config->shouldReceive('set')->with('services.google.client_secret', 'google_client_secret')->once();
        $this->config->shouldReceive('set')->with('services.google.redirect', 'https://test.example.com/auth/provider/google/callback')->once();

        // Pusher
        $this->config->shouldReceive('set')->with('broadcasting.connections.pusher.key', 'pusher_app_key')->once();
        $this->config->shouldReceive('set')->with('broadcasting.connections.pusher.secret', 'pusher_app_secret')->once();
        $this->config->shouldReceive('set')->with('broadcasting.connections.pusher.app_id', 'pusher_app_id')->once();
        $this->config->shouldReceive('set')->with('broadcasting.connections.pusher.options.cluster', 'pusher_app_cluster')->once();
        $this->config->shouldReceive('set')->with('broadcasting.connections.pusher.options.port', 443)->once();
        $this->config->shouldReceive('set')->with('broadcasting.connections.pusher.options.scheme', 'https')->once();

        // CORS
        $this->config->shouldReceive('set')->with('cors.allowed_origins', ['https://test.example.com', 'https://app.test.example.com'])->once();

        // Auth
        $this->config->shouldReceive('set')->with('auth.disable_socialite', false)->once();
        $this->config->shouldReceive('set')->with('auth.verify_email_before_login', true)->once();

        // Recaptcha
        $this->config->shouldReceive('set')->with('core.recaptcha.google.secret', 'recaptcha_google_secret')->once();

        // Context
        Context::shouldReceive('add')->with('tenant_id', $this->tenant->public_id)->once();

        // Act
        ConfigApplier::apply($this->tenant, $this->config);
    }

    #[TestDox('It should throw exception when tenant config is missing')]
    public function testThrowsExceptionWhenTenantConfigIsMissing(): void
    {
        // Arrange
        $this->tenant->shouldReceive('getEffectiveConfig')
            ->once()
            ->andReturnNull();

        // Act & Assert
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Tenant config is missing.');

        ConfigApplier::apply($this->tenant, $this->config);
    }
}
