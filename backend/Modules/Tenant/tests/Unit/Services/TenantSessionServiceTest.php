<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Illuminate\Contracts\Session\Session;
use Mockery;
use Modules\Tenant\app\Models\Tenant;
use Modules\Tenant\app\Services\TenantSessionService;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use Mockery\MockInterface;

#[CoversClass(TenantSessionService::class)]
#[Group('tenant-module')]
#[Group('services')]
class TenantSessionServiceTest extends TestCase
{
    private TenantSessionService $sessionService;
    private Session|MockInterface $sessionMock;

    #[Before]
    public function setupTest(): void
    {
        $this->sessionMock    = Mockery::mock(Session::class);
        $this->sessionService = new TenantSessionService($this->sessionMock);
    }

    /**
     * Test that hasTenant returns true when tenant exists in session.
     */
    public function testHasTenantReturnsTrueWhenTenantExists(): void
    {
        $this->sessionMock->shouldReceive('has')
            ->once()
            ->with('tenant')
            ->andReturn(true);

        $this->assertTrue($this->sessionService->hasTenant());
    }

    /**
     * Test that hasTenant returns false when tenant does not exist in session.
     */
    public function testHasTenantReturnsFalseWhenTenantDoesNotExist(): void
    {
        $this->sessionMock->shouldReceive('has')
            ->once()
            ->with('tenant')
            ->andReturn(false);

        $this->assertFalse($this->sessionService->hasTenant());
    }

    /**
     * Test that getTenant returns the tenant from the session.
     */
    public function testGetTenantReturnsNullWhenTenantDoesNotExist(): void
    {
        $this->sessionMock->shouldReceive('get')
            ->once()
            ->with('tenant')
            ->andReturnNull();

        $this->assertNull($this->sessionService->getTenant());
    }

    /**
     * Test that getTenant returns the tenant from the session.
     */
    public function testGetTenantReturnTenantWhenTenantExists(): void
    {
        $tenant = Tenant::factory()->make();

        $this->sessionMock->shouldReceive('get')
            ->once()
            ->with('tenant')
            ->andReturn($tenant->only(['public_id', 'name', 'domain']));

        $this->assertEquals(
            $tenant,
            $this->sessionService->getTenant(),
        );
    }

    /**
     * Test that setTenant stores the tenant in the session.
     */
    public function testSetTenantStoresTenantInSession(): void
    {
        $tenant = Tenant::factory()->make();

        $this->sessionMock->shouldReceive('put')
            ->once()
            ->with('tenant', $tenant->only(['public_id', 'name', 'domain']));

        $this->sessionService->setTenant(
            $tenant,
        );
    }
}
