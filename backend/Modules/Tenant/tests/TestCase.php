<?php

namespace Modules\Tenant\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery\MockInterface;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

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
        $this->tenant = Tenant::factory()->create([
            'domain' => config('quvel.default_api_domain'),
        ]);

        // Set TenantContext for tests
        $this->tenantContext = new TenantContext();
        $this->tenantContext->set($this->tenant);
        $this->app->instance(TenantContext::class, $this->tenantContext);
    }

    protected function seedMock(): void
    {
        $this->tenantContextMock = $this->mock(TenantContext::class);
    }

    /**
     * Bypass tenant middleware by mocking the tenant resolver.
     * This allows feature tests to work without domain resolution issues.
     */
    protected function bypassTenantMiddleware(): void
    {
        $tenantResolver = $this->mock(TenantResolver::class);
        $tenantResolver->shouldReceive('resolveTenant')
            ->andReturn($this->tenant);
        
        // Bind to both the interface and the configured implementation
        $this->app->instance(TenantResolver::class, $tenantResolver);
        
        // Get the configured resolver class (should be HostResolver)
        $configuredResolver = config('tenant.resolver');
        if ($configuredResolver && $configuredResolver !== TenantResolver::class) {
            $this->app->instance($configuredResolver, $tenantResolver);
        }
        
        // Mock TenantContext to always return our test tenant
        // This is needed because TenantContext is scoped and gets a fresh instance per request
        $tenantContextMock = $this->mock(TenantContext::class);
        $tenantContextMock->shouldReceive('get')->andReturn($this->tenant);
        $tenantContextMock->shouldReceive('set')->with($this->tenant);
        $tenantContextMock->shouldReceive('isBypassed')->andReturn(false);
        
        $this->app->instance(TenantContext::class, $tenantContextMock);
    }

    protected function createTenantConfig(): DynamicTenantConfig
    {
        $config = new DynamicTenantConfig([
            'app_url'             => 'https://api.example.com',
            'frontend_url'        => 'https://app.example.com',
            'internal_api_url'    => 'https://internal-api.example.com',
            'app_debug'           => true,
            'app_timezone'        => 'UTC',
            'app_key'             => 'base64:example',
            'app_name'            => 'Example App',
            'app_env'             => 'testing',
            'app_locale'          => 'en',
            'app_fallback_locale' => 'en',
            'log_channel'         => 'stack',
            'log_level'           => 'debug',
            'db_connection'       => 'mysql',
            'db_host'             => '127.0.0.1',
            'db_port'             => 3306,
            'db_database'         => 'quvel',
            'db_username'         => 'root',
            'db_password'         => '',
            'session_driver'      => 'file',
            'session_lifetime'    => 120,
            'session_encrypt'     => false,
            'session_path'        => '/',
            'session_domain'      => '',
            'cache_store'         => 'file',
            'cache_prefix'        => '',
            'redis_client'        => 'phpredis',
            'redis_host'          => '127.0.0.1',
            'redis_password'      => null,
            'redis_port'          => 6379,
            'mail_mailer'         => 'smtp',
            'mail_scheme'         => null,
            'mail_host'           => 'mailhog',
            'mail_port'           => 1025,
            'mail_username'       => null,
            'mail_password'       => null,
            'mail_from_address'   => 'no-reply@example.com',
            'mail_from_name'      => 'Example',
            'capacitor_scheme'    => null,
        ]);

        $config->setVisibility('app_url', TenantConfigVisibility::PUBLIC);
        $config->setVisibility('app_name', TenantConfigVisibility::PUBLIC);

        return $config;
    }
}
