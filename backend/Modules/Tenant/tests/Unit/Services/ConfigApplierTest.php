<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
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

        $this->tenant = Mockery::mock(Tenant::class);
        $this->config = Mockery::mock(ConfigRepository::class);
        $this->tenantConfig = Mockery::mock(TenantConfig::class);
    }

    #[TestDox('It should apply tenant config to app config')]
    public function testAppliesTenantConfigToAppConfig(): void
    {
        // Arrange
        // Set up tenant config properties
        $this->tenantConfig->appName = 'Test Tenant';
        $this->tenantConfig->appEnv = 'testing';
        $this->tenantConfig->appKey = 'test-key';
        $this->tenantConfig->appDebug = true;
        $this->tenantConfig->appUrl = 'https://test.example.com';
        $this->tenantConfig->frontendUrl = 'https://app.test.example.com';
        $this->tenantConfig->internalApiUrl = 'https://internal-api.test.example.com';
        $this->tenantConfig->capacitorScheme = 'test-scheme';
        $this->tenantConfig->appLocale = 'en';
        $this->tenantConfig->appFallbackLocale = 'en';
        $this->tenantConfig->logChannel = 'stack';
        $this->tenantConfig->logLevel = 'debug';
        $this->tenantConfig->dbConnection = 'mysql';
        $this->tenantConfig->dbHost = 'localhost';
        $this->tenantConfig->dbPort = 3306;
        $this->tenantConfig->dbDatabase = 'test_db';
        $this->tenantConfig->dbUsername = 'test_user';
        $this->tenantConfig->dbPassword = 'test_password';
        $this->tenantConfig->sessionDriver = 'file';
        $this->tenantConfig->sessionLifetime = 120;
        $this->tenantConfig->sessionEncrypt = false;
        $this->tenantConfig->sessionPath = '/';
        $this->tenantConfig->sessionDomain = 'test.example.com';
        $this->tenantConfig->sessionCookie = 'test_session';
        $this->tenantConfig->cacheStore = 'file';
        $this->tenantConfig->cachePrefix = 'test_';
        $this->tenantConfig->redisClient = 'phpredis';
        $this->tenantConfig->redisHost = 'localhost';
        $this->tenantConfig->redisPassword = null;
        $this->tenantConfig->redisPort = 6379;
        $this->tenantConfig->mailMailer = 'smtp';
        $this->tenantConfig->mailHost = 'smtp.example.com';
        $this->tenantConfig->mailPort = 587;
        $this->tenantConfig->mailUsername = 'test@example.com';
        $this->tenantConfig->mailPassword = 'mail_password';
        $this->tenantConfig->mailFromAddress = 'no-reply@example.com';
        $this->tenantConfig->mailFromName = 'Test App';
        $this->tenantConfig->awsAccessKeyId = 'aws_key';
        $this->tenantConfig->awsSecretAccessKey = 'aws_secret';
        $this->tenantConfig->awsDefaultRegion = 'us-west-2';
        $this->tenantConfig->awsBucket = 'test-bucket';
        $this->tenantConfig->awsUsePathStyleEndpoint = true;
        $this->tenantConfig->socialiteProviders = ['github', 'google'];
        $this->tenantConfig->socialiteNonceTtl = 3600;
        $this->tenantConfig->socialiteTokenTtl = 86400;
        $this->tenantConfig->hmacSecretKey = 'hmac_secret';
        $this->tenantConfig->oauthCredentials = [
            'github' => [
                'client_id' => 'github_client_id',
                'client_secret' => 'github_client_secret',
            ],
            'google' => [
                'client_id' => 'google_client_id',
                'client_secret' => 'google_client_secret',
            ],
        ];

        $this->tenant->shouldReceive('getEffectiveConfig')
            ->once()
            ->andReturn($this->tenantConfig);

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

        // Act
        ConfigApplier::apply($this->tenant, $this->config);

        // No assertion needed as we're verifying the expectations on the mock
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
