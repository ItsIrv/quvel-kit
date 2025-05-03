<?php

namespace Modules\Tenant\Tests\Feature\Actions;

use Illuminate\Cache\Repository as CacheRepository;
use Mockery;
use Modules\Tenant\Actions\TenantsDump;
use Modules\Tenant\Http\Resources\TenantDumpResource;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TenantFindService;
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
    public function testTenantsDumpReturnsCachedTenants(): void
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
        $action = new TenantsDump();
        $result = $action->__invoke($tenantFindServiceMock, $cacheMock);

        // Assert correct response type
        $this->assertEquals(TenantDumpResource::collection($cachedTenants), $result);
    }

    /**
     * Test that `TenantsDump` fetches and caches tenants when cache is empty.
     */
    public function testTenantsDumpFetchesWhenCacheEmpty(): void
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
        $action = new TenantsDump();
        $result = $action->__invoke($tenantFindServiceMock, $cacheMock);

        // Assert correct response type
        $this->assertEquals(TenantDumpResource::collection($freshTenants), $result);
    }
}
