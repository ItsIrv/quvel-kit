<?php

namespace Modules\Tenant\Tests\Unit\Http\Middleware;

use App\Services\FrontendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Modules\Tenant\app\Http\Middleware\TenantMiddleware;
use Modules\Tenant\app\Models\Tenant;
use Modules\Tenant\app\Services\TenantResolverService;
use Modules\Tenant\Enums\TenantError;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantMiddleware::class)]
#[Group('tenant-module')]
#[Group('middleware')]
class TenantMiddlewareTest extends TestCase
{
    private TenantResolverService|MockInterface $tenantResolver;
    private FrontendService|MockInterface $frontendService;
    private TenantMiddleware $middleware;
    private Request|MockInterface $request;

    #[Before]
    public function setupTest(): void
    {
        $this->tenantResolver  = Mockery::mock(TenantResolverService::class);
        $this->frontendService = Mockery::mock(FrontendService::class);
        $this->middleware      = new TenantMiddleware($this->tenantResolver, $this->frontendService);
        $this->request         = Mockery::mock(Request::class);
    }

    /**
     * Test middleware allows request when tenant exists.
     */
    public function testMiddlewarePassesWhenTenantExists(): void
    {
        $tenant = Tenant::factory()->create();
        $this->request->shouldReceive('getHost')
            ->once()
            ->andReturn('example.com');

        $this->tenantResolver->shouldReceive('resolveTenant')
            ->once()
            ->with('example.com')
            ->andReturn($tenant);

        $next = fn ($req) => 'next-called';

        $result = $this->middleware->handle($this->request, $next);

        $this->assertEquals('next-called', $result);
    }

    /**
     * Test middleware throws an exception when no tenant exists in local.
     */
    public function testMiddlewareThrowsExceptionWhenNoTenantInLocal(): void
    {
        $this->request->shouldReceive('getHost')
            ->once()
            ->andReturn('example.com');

        $this->tenantResolver->shouldReceive('resolveTenant')
            ->once()
            ->with('example.com')
            ->andReturn(null);

        $this->app->detectEnvironment(fn () => 'local');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(TenantError::NOT_FOUND->value);

        $next = fn ($req) => 'next-called';

        $this->middleware->handle($this->request, $next);
    }

    /**
     * Test middleware redirects when no tenant exists in production.
     */
    public function testMiddlewareRedirectsWhenNoTenantInProduction(): void
    {
        $this->request->shouldReceive('getHost')
            ->once()
            ->andReturn('example.com');

        $this->tenantResolver->shouldReceive('resolveTenant')
            ->once()
            ->with('example.com')
            ->andReturn(null);

        $this->frontendService->shouldReceive('redirectError')
            ->once()
            ->with(TenantError::NOT_FOUND->value)
            ->andReturn(new RedirectResponse('redirect-response'));

        $this->app->detectEnvironment(fn () => 'production');

        $next = fn ($req) => 'next-called';

        $result = $this->middleware->handle($this->request, $next);

        $this->assertInstanceOf(RedirectResponse::class, $result);
        $this->assertEquals('redirect-response', $result->getTargetUrl());
    }
}
