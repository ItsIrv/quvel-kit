<?php

namespace Modules\Tenant\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\database\seeders\TenantSeeder;
use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\TenantConfig;

/**
 * Provides methods to seed the tenant and set the tenant context for the application.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected Tenant $tenant;

    protected TenantContext $tenantContext;

    protected TenantContext|MockInterface $tenantContextMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
    }

    /**
     * Seed the tenant and set the tenant context for the application.
     * All feature endpoints need this as the tenant middleware is global.
     */
    protected function seedTenant(): void
    {
        $this->seed(TenantSeeder::class);

        $this->tenant = Tenant::where(
            'domain',
            '=',
            config('quvel.default_api_domain'),
        )->first();

        // Set TenantContext for tests
        $this->tenantContext = new TenantContext();
        $this->tenantContext->set($this->tenant);
        $this->app->instance(TenantContext::class, $this->tenantContext);
    }

    protected function seedMock(): void
    {
        $this->tenantContextMock = $this->mock(TenantContext::class);
    }

    protected function createTenantConfig(): TenantConfig
    {
        return new TenantConfig(
            appUrl: 'https://api.example.com',
            frontendUrl: 'https://app.example.com',
            internalApiUrl: 'https://internal-api.example.com',
            appDebug: true,
            appTimezone: 'UTC',
            appKey: 'base64:example',
            appName: 'Example App',
            appEnv: 'testing',
            appLocale: 'en',
            appFallbackLocale: 'en',
            appFakerLocale: 'en',
            logChannel: 'stack',
            logLevel: 'debug',
            dbConnection: 'mysql',
            dbHost: '127.0.0.1',
            dbPort: 3306,
            dbDatabase: 'quvel',
            dbUsername: 'root',
            dbPassword: '',
            sessionDriver: 'file',
            sessionLifetime: 120,
            sessionEncrypt: false,
            sessionPath: '/',
            sessionDomain: '',
            cacheStore: 'file',
            cachePrefix: '',
            redisClient: 'phpredis',
            redisHost: '127.0.0.1',
            redisPassword: null,
            redisPort: 6379,
            mailMailer: 'smtp',
            mailScheme: null,
            mailHost: 'mailhog',
            mailPort: 1025,
            mailUsername: null,
            mailPassword: null,
            mailFromAddress: 'no-reply@example.com',
            mailFromName: 'Example',
            capacitorScheme: null,
            visibility: [
                'app_url'  => TenantConfigVisibility::PUBLIC ,
                'app_name' => TenantConfigVisibility::PUBLIC ,
            ],
        );
    }
}
