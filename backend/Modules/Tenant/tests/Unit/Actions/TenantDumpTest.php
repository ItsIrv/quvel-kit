<?php

namespace Modules\Tenant\Tests\Unit\Actions;

use Mockery;
use Mockery\MockInterface;
use Modules\Tenant\Actions\TenantDump;
use Modules\Tenant\app\Models\Tenant;
use Modules\Tenant\app\Services\TenantSessionService;
use Modules\Tenant\Enums\TenantError;
use Modules\Tenant\Transformers\TenantTransformer;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

#[CoversClass(TenantDump::class)]
#[Group('tenant')]
class TenantDumpTest extends TestCase
{
    private TenantSessionService|MockInterface $sessionService;
    private TenantDump $action;

    #[Before]
    public function setupTest(): void
    {
        $this->sessionService = Mockery::mock(TenantSessionService::class);
        $this->action         = new TenantDump($this->sessionService);
    }

    /**
     * Test `TenantDump` successfully transforms a tenant.
     */
    public function testTenantDumpReturnsTenant(): void
    {
        $tenant = Tenant::factory()->make();

        $this->sessionService->shouldReceive('getTenant')
            ->once()
            ->andReturn($tenant);

        $result = ($this->action)();

        $this->assertInstanceOf(TenantTransformer::class, $result);
        $this->assertEquals($tenant->public_id, $result->resolve()['id']);
    }

    /**
     * Test `TenantDump` throws when no tenant exists.
     */
    public function testTenantDumpThrowsWhenNoTenant(): void
    {
        $this->sessionService->shouldReceive('getTenant')
            ->once()
            ->andReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage(TenantError::NOT_FOUND->value);

        ($this->action)();
    }
}
