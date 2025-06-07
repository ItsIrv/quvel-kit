<?php

namespace Modules\Tenant\Tests\Unit\Http\Middleware;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Request;
use Mockery;
use Mockery\MockInterface;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Contracts\TenantResolver;
use Modules\Tenant\Http\Middleware\TenantMiddleware;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\ConfigurationPipeline;
use Modules\Tenant\Services\TenantExclusionRegistry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(TenantMiddleware::class)]
#[Group('tenant-module')]
#[Group('tenant-middleware')]
final class TenantMiddlewareTest extends TestCase
{
    /**
     * @var TenantResolver|MockInterface
     */
    protected TenantResolver $tenantResolver;

    /**
     * @var TenantContext|MockInterface
     */
    protected TenantContext $tenantContext;

    /**
     * @var ConfigurationPipeline|MockInterface
     */
    protected ConfigurationPipeline $configPipeline;

    /**
     * @var ConfigRepository|MockInterface
     */
    protected ConfigRepository $config;

    /**
     * @var TenantExclusionRegistry|MockInterface
     */
    protected TenantExclusionRegistry $exclusionRegistry;

    /**
     * @var Request|MockInterface
     */
    protected Request $request;

    /**
     * @var TenantMiddleware
     */
    private TenantMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantResolver = Mockery::mock(TenantResolver::class);
        $this->tenantContext  = Mockery::mock(TenantContext::class);
        $this->configPipeline = Mockery::mock(ConfigurationPipeline::class);
        $this->config         = Mockery::mock(ConfigRepository::class);
        $this->exclusionRegistry = Mockery::mock(TenantExclusionRegistry::class);
        $this->request        = Mockery::mock(Request::class);

        $this->middleware = new TenantMiddleware(
            $this->tenantResolver,
            $this->tenantContext,
            $this->configPipeline,
            $this->config,
            $this->exclusionRegistry,
        );
    }

    #[TestDox('It should resolve tenant, set context, apply config, and proceed with request')]
    public function testHandleSetsTenantInContextAndProceeds(): void
    {
        // Arrange
        $tenant           = Mockery::mock(Tenant::class);
        $expectedResponse = 'response';

        // Mock exclusion registry
        $this->exclusionRegistry->shouldReceive('getExcludedPaths')
            ->once()
            ->andReturn([]);
        
        $this->exclusionRegistry->shouldReceive('getExcludedPatterns')
            ->once()
            ->andReturn([]);

        // Mock config
        $this->config->shouldReceive('get')
            ->with('tenant.excluded_paths', [])
            ->andReturn([]);
        
        $this->config->shouldReceive('get')
            ->with('tenant.excluded_patterns', [])
            ->andReturn([]);

        $this->tenantResolver->shouldReceive('resolveTenant')
            ->once()
            ->andReturn($tenant);

        $this->tenantContext->shouldReceive('set')
            ->once()
            ->with($tenant);

        $this->configPipeline->shouldReceive('apply')
            ->once()
            ->with($tenant, $this->config);

        $next = function ($passedRequest) use ($expectedResponse) {
            return $expectedResponse;
        };

        // Act
        $result = $this->middleware->handle($this->request, $next);

        // Assert
        $this->assertSame($expectedResponse, $result);
    }
}
