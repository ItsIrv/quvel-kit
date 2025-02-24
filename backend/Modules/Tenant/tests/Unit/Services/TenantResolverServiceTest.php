<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Modules\Tenant\App\Models\Tenant;
use Modules\Tenant\App\Services\TenantFindService;
use Modules\Tenant\App\Services\TenantResolverService;
use Modules\Tenant\App\Services\TenantSessionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

#[CoversClass(TenantResolverService::class)]
#[Group('tenant-module')]
#[Group('services')]
class TenantResolverServiceTest extends TestCase
{
    private TenantFindService|MockObject $tenantFindService;
    private TenantSessionService|MockObject $tenantSessionService;
    private TenantResolverService $tenantResolverService;

    protected function setUp(): void
    {
        $this->tenantFindService    = $this->createMock(TenantFindService::class);
        $this->tenantSessionService = $this->createMock(TenantSessionService::class);

        $this->tenantResolverService = new TenantResolverService(
            $this->tenantFindService,
            $this->tenantSessionService,
        );
    }

    /**
     * Test that resolveTenant returns tenant from session if available.
     */
    public function testResolveTenantReturnsTenantFromSession(): void
    {
        $tenant = new Tenant(['domain' => 'example.com']);

        $this->tenantSessionService
            ->expects($this->once())
            ->method('hasTenant')
            ->willReturn(true);

        $this->tenantSessionService
            ->expects($this->once())
            ->method('getTenant')
            ->willReturn($tenant);

        $resolvedTenant = $this->tenantResolverService->resolveTenant(
            'example.com',
        );

        $this->assertSame($tenant, $resolvedTenant);
    }

    /**
     * Test that resolveTenant queries the database if tenant is not in session.
     */
    public function testResolveTenantQueriesDatabaseIfNotInSession(): void
    {
        $tenant = new Tenant(['domain' => 'example.com']);

        $this->tenantSessionService
            ->expects($this->once())
            ->method('hasTenant')
            ->willReturn(false);

        $this->tenantFindService
            ->expects($this->once())
            ->method('findTenantByDomain')
            ->with('example.com')
            ->willReturn($tenant);

        $this->tenantSessionService
            ->expects($this->once())
            ->method('setTenant')
            ->with($tenant);

        $resolvedTenant = $this->tenantResolverService->resolveTenant(
            'example.com',
        );

        $this->assertSame($tenant, $resolvedTenant);
    }

    /**
     * Test that resolveTenant returns null if tenant is not found.
     */
    public function testResolveTenantReturnsNullIfTenantNotFound(): void
    {
        $this->tenantSessionService
            ->expects($this->once())
            ->method('hasTenant')
            ->willReturn(false);

        $this->tenantFindService
            ->expects($this->once())
            ->method('findTenantByDomain')
            ->with('example.com')
            ->willReturn(null);

        $resolvedTenant = $this->tenantResolverService->resolveTenant(
            'example.com',
        );

        $this->assertNull($resolvedTenant);
    }
}
