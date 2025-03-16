<?php

namespace Modules\Tenant\Tests\Feature\Actions;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Mockery;
use Modules\Tenant\Actions\TenantsDump;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\Transformers\TenantDumpTransformer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantsDump::class)]
#[Group('tenant-module')]
#[Group('tenant-actions')]
class TenantsDumpFeatureTest extends TestCase
{
    /**
     * Test that `TenantsDump` correctly returns cached tenants.
     */
    public function test_tenants_dump_returns_cached_tenants(): void
    {
        // Use $this->createMock() for TenantFindService since it's a normal class
        $tenantFindServiceMock = $this->createMock(TenantFindService::class);

        // Use Mockery::mock() for CacheRepository since Laravel handles caching internally
        $cacheMock = Mockery::mock(CacheRepository::class);

        // Sample tenant data as Eloquent Collection
        $cachedTenants = Tenant::all();

        // Simulate cache hit
        $cacheMock->shouldReceive('has')->once()->with('tenants')->andReturn(true);
        $cacheMock->shouldReceive('get')->once()->with('tenants')->andReturn($cachedTenants);

        // Execute the action
        $action = new TenantsDump;
        $result = $action->__invoke($tenantFindServiceMock, $cacheMock);

        // Assert correct response type
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(TenantDumpTransformer::collection($cachedTenants), $result);
    }

    /**
     * Test that `TenantsDump` fetches and caches tenants when cache is empty.
     */
    public function test_tenants_dump_fetches_when_cache_empty(): void
    {
        // Use $this->createMock() for TenantFindService
        $tenantFindServiceMock = $this->createMock(TenantFindService::class);

        // Use Mockery::mock() for CacheRepository
        $cacheMock = Mockery::mock(CacheRepository::class);

        // Sample fresh tenant data as Eloquent Collection
        $freshTenants = Tenant::all();

        // Simulate cache miss
        $cacheMock->shouldReceive('has')->once()->with('tenants')->andReturn(false);
        $cacheMock->shouldReceive('put')->once()->with('tenants', $freshTenants, 60);

        // Simulate service fetching tenants
        $tenantFindServiceMock->expects($this->once())
            ->method('findAll')
            ->willReturn($freshTenants);

        // Execute the action
        $action = new TenantsDump;
        $result = $action->__invoke($tenantFindServiceMock, $cacheMock);

        // Assert correct response type
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(TenantDumpTransformer::collection($freshTenants), $result);
    }
}
