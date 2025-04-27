<?php

namespace Modules\Tenant\Tests\Unit\Actions;

use Modules\Tenant\Actions\TenantDump;
use Modules\Tenant\Contexts\TenantContext;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantDump::class)]
#[Group('tenant-module')]
#[Group('tenant-actions')]
class TenantDumpTest extends TestCase
{
    /**
     * Test that `TenantDump` correctly returns a `TenantDumpTransformer` with the current tenant.
     */
    public function test_tenant_dump_returns_transformer_with_tenant(): void
    {
        $tenantContext = $this->createMock(
            TenantContext::class,
        );

        $tenantContext->method('get')
            ->willReturn($this->tenant);

        $action = new TenantDump();

        $result = $action->__invoke($tenantContext);

        $this->assertEquals($this->tenant, $result->resource);
    }
}
