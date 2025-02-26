<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Modules\Tenant\app\Contexts\TenantContext;
use Modules\Tenant\app\Models\Tenant;
use Illuminate\Http\Request;

abstract class TestCase extends BaseTestCase
{
    use WithFaker;
    use RefreshDatabase;

    protected Tenant $tenant;
    protected TenantContext $tenantContext;
    protected TenantContext|MockInterface $tenantContextMock;

    public function setUp(): void
    {
        parent::setUp();

        $this->seedTenant();
    }

    /**
     * Seed the tenant and set the tenant context for the application.
     * All feature endpoints need this as the tenant middleware is global.
     * @return void
     */
    protected function seedTenant(): void
    {
        // Extract host from config or default to app.url
        $apiDomain = parse_url(config('app.url'))['host'] ?? config('app.url');

        // Create a tenant for tests
        $this->tenant = Tenant::factory()->create([
            'domain' => $apiDomain,
        ]);

        // Set TenantContext for tests
        $this->tenantContext = new TenantContext();
        $this->tenantContext->set($this->tenant);
        $this->app->instance(TenantContext::class, $this->tenantContext);

        // Bind request host in the container
        $this->app->bind(Request::class, function () use ($apiDomain) {
            return Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => $apiDomain]);
        });
    }

    protected function seedMock(): void
    {
        $this->tenantContextMock = $this->mock(TenantContext::class);
    }
}
