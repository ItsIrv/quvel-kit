<?php

namespace Modules\Tenant\Tests\Unit\Http\Middleware;

use Illuminate\Http\Request;
use Mockery\MockInterface;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Http\Middleware\TenantMiddleware;
use Modules\Tenant\Services\HostResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantMiddleware::class)]
#[Group('tenant-module')]
#[Group('tenant-middleware')]
class TenantMiddlewareTest extends TestCase
{
    private HostResolver|MockInterface $tenantResolver;

    private TenantMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantResolver    = $this->mock(HostResolver::class);
        $this->tenantContextMock = $this->mock(TenantContext::class);
        $this->middleware        = new TenantMiddleware(
            $this->tenantResolver,
            $this->tenantContextMock,
        );
    }

    /**
     * Test that the middleware sets the tenant in the context and allows the request to proceed.
     */
    public function testHandleSetsTenantInContextAndProceeds(): void
    {
        $request = $this->mock(Request::class);

        $this->tenantResolver->shouldReceive('resolveTenant')
            ->once()
            ->with($request)
            ->andReturn($this->tenant);

        $this->tenantContextMock->shouldReceive('set')
            ->once()
            ->with($this->tenant);

        $next = fn ($req) => 'next-called';

        $result = $this->middleware->handle($request, $next);

        $this->assertEquals('next-called', $result);
    }
}
