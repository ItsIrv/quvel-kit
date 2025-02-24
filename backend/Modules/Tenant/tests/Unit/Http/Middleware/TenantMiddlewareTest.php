<?php

namespace Modules\Tenant\Tests\Unit\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Modules\Tenant\app\Models\Tenant;
use Modules\Tenant\app\Services\TenantResolverService;
use Modules\Tenant\app\Http\Middleware\TenantMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantMiddleware::class)]
#[Group('tenant')]
class TenantMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private TenantMiddleware $middleware;
    private TenantResolverService $resolverMock;

    #[Before]
    public function setupTest(): void
    {
        $this->resolverMock = $this->createMock(TenantResolverService::class);
        $this->middleware   = new TenantMiddleware(
            $this->resolverMock,
        );
    }

    // public function testHandlesExistingTenant(): void
    // {
    //     $tenant = Tenant::factory()->create();

    //     $this->resolverMock->method('resolveTenant')->willReturn($tenant);

    //     $request = new Request([], [], [], [], [], ['HTTP_HOST' => $tenant->domain]);

    //     $response = $this->middleware->handle($request, fn () => response()->json(['tenant' => $tenant->public_id]));

    //     $this->assertEquals(200, $response->getStatusCode());
    //     $this->assertJson($response->getContent());
    // }

    // public function testThrowsNotFoundWhenTenantDoesNotExist(): void
    // {
    //     $this->resolverMock->method('resolveTenant')->willReturn(null);

    //     $request = new Request([], [], [], [], [], ['HTTP_HOST' => 'unknown.quvel.127.0.0.1.nip.io']);

    //     $this->expectException(NotFoundHttpException::class);

    //     $this->middleware->handle($request, fn () => response()->json([]));
    // }
}
