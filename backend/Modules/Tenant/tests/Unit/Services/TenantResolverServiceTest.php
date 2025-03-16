<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Illuminate\Http\Request;
use Mockery;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Services\TenantFindService;
use Modules\Tenant\Services\TenantResolverService;
use Modules\Tenant\Services\TenantSessionService;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

#[CoversClass(TenantResolverService::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
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

        $this->requestMock = Mockery::mock(Request::class);

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

        $this->requestMock->shouldReceive('getHost')
            ->with()
            ->andReturn('host.com');

        $this->tenantFindService->expects(
            $this->once(),
        )->method('findTenantByDomain')
            ->with('host.com')
            ->willReturn($this->tenant);

        $this->assertSame(
            $this->tenant,
            $this->tenantResolverService->resolveTenant($this->requestMock),
        );
    }

    /**
     * Test that resolveTenant throws TenantNotFoundException when tenant is not found by domain.
     */
    public function testResolveTenantThrowsExceptionWhenTenantNotFound(): void
    {
        $this->tenantSessionService->expects($this->once())
            ->method('getTenant')
            ->willReturn(null);

        $this->tenantFindService->expects($this->once())
            ->method('findTenantByDomain')
            ->willReturn(null);

        $this->requestMock->shouldReceive('getHost')->andReturn('nonexistent.domain')->once();
        $this->expectException(TenantNotFoundException::class);

        $this->tenantResolverService->resolveTenant($this->requestMock);
    }
}
