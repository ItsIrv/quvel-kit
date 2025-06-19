<?php

namespace Modules\Tenant\Tests\Unit\Actions;

use Illuminate\Http\Request;
use Modules\Tenant\Actions\TenantProtectedConfig;
use Modules\Tenant\Contracts\TenantResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantProtectedConfig::class)]
#[Group('tenant-module')]
#[Group('tenant-actions')]
class TenantProtectedConfigTest extends TestCase
{
    /**
     * Test that `TenantProtectedConfig` correctly returns a `TenantDumpResource` with the tenant.
     */
    public function testTenantProtectedConfigReturnsResourceWithTenant(): void
    {
        $tenantResolver = $this->createMock(TenantResolver::class);
        $tenantResolver->expects($this->once())
            ->method('resolveTenant')
            ->willReturn($this->tenant);

        $this->app->instance(config('tenant.resolver'), $tenantResolver);

        $request = $this->createMock(Request::class);

        $action = new TenantProtectedConfig();

        $result = $action->__invoke($request);

        $this->assertInstanceOf(\Modules\Tenant\Http\Resources\TenantDumpResource::class, $result);
        $this->assertEquals($this->tenant, $result->resource);
    }
}
