<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Illuminate\Http\Request;
use Modules\Tenant\App\Services\TenantFindService;
use Modules\Tenant\App\Services\TenantResolverService;
use Modules\Tenant\App\Services\TenantSessionService;
use PHPUnit\Framework\Attributes\Before;
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
    private Request|MockObject $requestMock;

    #[Before]
    protected function setUpTest(): void
    {
        $this->tenantFindService = $this->createMock(
            TenantFindService::class,
        );

        $this->tenantSessionService = $this->createMock(
            TenantSessionService::class,
        );

        $this->requestMock = Request::capture();

        $this->tenantResolverService = new TenantResolverService(
            $this->tenantFindService,
            $this->tenantSessionService,
        );
    }

    /**
     * Test that resolveTenant returns tenant from session if available.
     */
    public function testResolveTenantReturnsTenantFromSessionIfAvailable(): void
    {
        $this->tenantSessionService->expects(
            $this->once(),
        )
            ->method('getTenant')
            ->willReturn($this->tenant);

        $this->assertSame(
            $this->tenant,
            $this->tenantResolverService->resolveTenant($this->requestMock),
        );
    }

    /**
     * Test that resolveTenant returns tenant from find service if available.
     */
    public function testResolveTenantReturnsTenantFromFindServiceIfAvailable(): void
    {
        $this->tenantSessionService->expects(
            $this->once(),
        )
            ->method('getTenant')
            ->willReturn(null);

        $this->tenantFindService->expects(
            $this->once(),
        )->method('findTenantByDomain')
            ->with($this->requestMock->getHost())
            ->willReturn($this->tenant);

        $this->assertSame(
            $this->tenant,
            $this->tenantResolverService->resolveTenant($this->requestMock),
        );
    }
}
