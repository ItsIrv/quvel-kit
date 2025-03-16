<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Illuminate\Contracts\Session\Session;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TenantSessionService;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Tests\TestCase;

#[CoversClass(TenantSessionService::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
class TenantSessionServiceTest extends TestCase
{
    private TenantSessionService $sessionService;

    private Session|MockObject $sessionMock;

    #[Before]
    public function setupTest(): void
    {
        $this->sessionMock = $this->createMock(Session::class);
        $this->sessionService = new TenantSessionService($this->sessionMock);
    }

    /**
     * Test that hasTenant returns true when tenant exists in session.
     */
    public function test_has_tenant_returns_true_when_tenant_exists(): void
    {
        $this->sessionMock->expects($this->once())
            ->method('has')
            ->with('tenant')
            ->willReturn(true);

        $this->assertTrue($this->sessionService->hasTenant());
    }

    /**
     * Test that hasTenant returns false when tenant does not exist in session.
     */
    public function test_has_tenant_returns_false_when_tenant_does_not_exist(): void
    {
        $this->sessionMock->expects($this->once())
            ->method('has')
            ->with('tenant')
            ->willReturn(false);

        $this->assertFalse($this->sessionService->hasTenant());
    }

    /**
     * Test that getTenant returns null when tenant does not exist.
     */
    public function test_get_tenant_returns_null_when_tenant_does_not_exist(): void
    {
        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('tenant')
            ->willReturn(null);

        $this->assertNull($this->sessionService->getTenant());
    }

    /**
     * Test that getTenant returns tenant when tenant exists.
     */
    public function test_get_tenant_returns_tenant_when_tenant_exists(): void
    {
        $tenant = Tenant::factory()->make();

        $this->sessionMock->expects($this->once())
            ->method('get')
            ->with('tenant')
            ->willReturn($tenant->only(['public_id', 'name', 'domain']));

        $this->assertEquals(
            $tenant,
            $this->sessionService->getTenant(),
        );
    }

    /**
     * Test that setTenant stores the tenant in the session.
     */
    public function test_set_tenant_stores_tenant_in_session(): void
    {
        $tenant = Tenant::factory()->make();

        $this->sessionMock->expects($this->once())
            ->method('put')
            ->with('tenant', $tenant->only([
                'public_id',
                'name',
                'domain',
                'created_at',
                'updated_at',
            ]));

        $this->sessionService->setTenant($tenant);
    }
}
