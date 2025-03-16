<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\database\seeders\TenantSeeder;
use Modules\Tenant\Models\Tenant;

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
        $this->seed(TenantSeeder::class);

        // Create a tenant for tests, match the static .env
        $this->tenant = Tenant::where(
            'domain',
            '=',
            'api.quvel.192.168.86.20.nip.io',
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
}
