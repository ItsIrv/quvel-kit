<?php

namespace Modules\Tenant\Tests\Unit\Actions;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Collection;
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
class TenantsDumpTest extends TestCase
{
    /**
     * Test that `TenantsDump` correctly returns cached tenants when available.
     */
    public function testTenantsDumpReturnsCachedTenants(): void
    {
        // Mock dependencies
        $cache = $this->createMock(CacheRepository::class);
        $tenantFindService = $this->createMock(TenantFindService::class);

        // Sample tenant data as Eloquent Collection
        $cachedTenants = new Collection([
            new Tenant(['id' => 1, 'name' => 'Tenant A']),
            new Tenant(['id' => 2, 'name' => 'Tenant B']),
        ]);

        // Simulate cache hit
        $cache->expects($this->once())
            ->method('has')
            ->with('tenants')
            ->willReturn(true);

        $cache->expects($this->once())
            ->method('get')
            ->with('tenants')
            ->willReturn($cachedTenants);

        // Execute the action
        $action = new TenantsDump();
        $result = $action->__invoke($tenantFindService, $cache);

        // Assert correct response type
        $this->assertEquals(TenantDumpResource::collection($cachedTenants), $result);
    }

    /**
     * Test that `TenantsDump` fetches tenants when cache is empty.
     */
    public function testTenantsDumpFetchesWhenCacheEmpty(): void
    {
        // Mock dependencies
        $cache = $this->createMock(CacheRepository::class);
        $tenantFindService = $this->createMock(TenantFindService::class);

        // Sample fresh tenant data as Eloquent Collection
        $freshTenants = new Collection([
            new Tenant(['id' => 1, 'name' => 'Tenant A']),
            new Tenant(['id' => 2, 'name' => 'Tenant B']),
        ]);

        // Simulate cache miss
        $cache->expects($this->once())
            ->method('has')
            ->with('tenants')
            ->willReturn(false);

        $cache->expects($this->once())
            ->method('put')
            ->with('tenants', $freshTenants, 60);

        // Simulate service fetching tenants
        $tenantFindService->expects($this->once())
            ->method('findAll')
            ->willReturn($freshTenants);

        // Execute the action
        $action = new TenantsDump();
        $result = $action->__invoke($tenantFindService, $cache);

        // Assert correct response type
        $this->assertEquals(TenantDumpResource::collection($freshTenants), $result);
    }
}
