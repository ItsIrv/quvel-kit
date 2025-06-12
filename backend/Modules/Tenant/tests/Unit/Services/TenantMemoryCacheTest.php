<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TenantMemoryCache;
use Modules\Tenant\Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;

#[CoversClass(TenantMemoryCache::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
class TenantMemoryCacheTest extends TestCase
{
    private TenantMemoryCache $memoryCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->memoryCache = app(TenantMemoryCache::class);
        $this->memoryCache->clearAll();
    }

    #[TestDox('It should cache and retrieve tenants')]
    public function testItCachesAndRetrievesTenants(): void
    {
        $tenant = Tenant::factory()->create(['domain' => 'test.example.com']);

        // Should return null initially
        $this->assertNull($this->memoryCache->getTenant('test.example.com'));

        // Cache the tenant
        $this->memoryCache->cacheTenant('test.example.com', $tenant);

        // Should return the cached tenant
        $cachedTenant = $this->memoryCache->getTenant('test.example.com');
        $this->assertInstanceOf(Tenant::class, $cachedTenant);
        $this->assertEquals($tenant->id, $cachedTenant->id);
        $this->assertEquals('test.example.com', $cachedTenant->domain);
    }

    #[TestDox('It should invalidate tenant cache')]
    public function testItInvalidatesTenantCache(): void
    {
        $tenant = Tenant::factory()->create(['domain' => 'test.example.com']);

        // Cache the tenant
        $this->memoryCache->cacheTenant('test.example.com', $tenant);
        $this->assertNotNull($this->memoryCache->getTenant('test.example.com'));

        // Invalidate cache
        $this->memoryCache->invalidateTenant('test.example.com');
        $this->assertNull($this->memoryCache->getTenant('test.example.com'));
    }

    #[TestDox('It should clear all cache')]
    public function testItClearsAllCache(): void
    {
        $tenant1 = Tenant::factory()->create(['domain' => 'test1.example.com']);
        $tenant2 = Tenant::factory()->create(['domain' => 'test2.example.com']);

        // Cache multiple tenants
        $this->memoryCache->cacheTenant('test1.example.com', $tenant1);
        $this->memoryCache->cacheTenant('test2.example.com', $tenant2);

        $this->assertNotNull($this->memoryCache->getTenant('test1.example.com'));
        $this->assertNotNull($this->memoryCache->getTenant('test2.example.com'));

        // Clear all cache
        $this->memoryCache->clearAll();

        $this->assertNull($this->memoryCache->getTenant('test1.example.com'));
        $this->assertNull($this->memoryCache->getTenant('test2.example.com'));
    }

    #[TestDox('It should detect Octane environment')]
    public function testItDetectsOctaneEnvironment(): void
    {
        // Method exists and returns a boolean
        $this->assertIsBool($this->memoryCache->isOctaneEnvironment());
    }

    #[TestDox('It should handle cache expiration')]
    public function testItHandlesCacheExpiration(): void
    {
        // Mock config to have very short TTL
        $this->app['config']->set('tenant.tenant_cache.resolver_ttl', 1);

        $tenant = Tenant::factory()->create(['domain' => 'test-expire.example.com']);

        // Cache the tenant
        $this->memoryCache->cacheTenant('test-expire.example.com', $tenant);
        $this->assertNotNull($this->memoryCache->getTenant('test-expire.example.com'));

        // Wait for expiration
        sleep(2);

        // Should return null due to expiration
        $this->assertNull($this->memoryCache->getTenant('test-expire.example.com'));
    }
}
