<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Illuminate\Cache\Repository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Request as LaravelRequest;
use Mockery;
use Mockery\MockInterface;
use Modules\Core\Services\FrontendService;
use Modules\Tenant\Enums\TenantHeader;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\Services\HostResolver;
use Modules\Tenant\Services\RequestPrivacy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ServerBag;
use Tests\TestCase;

#[CoversClass(HostResolver::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
final class HostResolverTest extends TestCase
{
    /**
     * @var FindService|MockInterface
     */
    private FindService $tenantFindService;

    /**
     * @var RequestPrivacy|MockInterface
     */
    private RequestPrivacy $requestPrivacyService;

    /**
     * @var Repository|MockInterface
     */
    private Repository $cache;

    /**
     * @var Request|MockInterface
     */
    private Request $request;

    /**
     * @var FrontendService|MockInterface
     */
    private FrontendService $frontendService;

    /**
     * @var Application|MockInterface
     */
    private Application $application;

    /**
     * @var ConfigRepository|MockInterface
     */
    private ConfigRepository $config;

    /**
     * @var HostResolver
     */
    private HostResolver $hostResolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantFindService     = Mockery::mock(FindService::class);
        $this->requestPrivacyService = Mockery::mock(RequestPrivacy::class);
        $this->cache                 = Mockery::mock(Repository::class);
        // Use Mockery::mock with LaravelRequest to avoid headers initialization issue
        $this->request         = Mockery::mock(LaravelRequest::class);
        $this->frontendService = Mockery::mock(FrontendService::class);
        $this->application     = Mockery::mock(Application::class);
        $this->config          = Mockery::mock(ConfigRepository::class);

        $this->hostResolver = new HostResolver(
            $this->tenantFindService,
            $this->requestPrivacyService,
            $this->cache,
            $this->request,
            $this->frontendService,
            $this->application,
            $this->config,
        );
    }

    #[TestDox('It should resolve tenant from database in local environment')]
    public function testResolveTenantFromDatabaseInLocalEnvironment(): void
    {
        // Arrange
        $host     = 'example.com';
        $tenant   = Mockery::mock(Tenant::class);
        $cacheTtl = 3600;

        $this->application->shouldReceive('environment')
            ->once()
            ->with('local')
            ->andReturn(true);

        $this->request->shouldReceive('getHost')
            ->once()
            ->andReturn($host);

        $this->request->shouldReceive('header')
            ->once()
            ->with(TenantHeader::TENANT_DOMAIN->value)
            ->andReturn('');

        $this->tenantFindService->shouldReceive('findTenantByDomain')
            ->once()
            ->with($host)
            ->andReturn($tenant);

        // Act
        $result = $this->hostResolver->resolveTenant();

        // Assert
        $this->assertSame($tenant, $result);
    }

    #[TestDox('It should resolve tenant from cache in non-local environment')]
    public function testResolveTenantFromCacheInNonLocalEnvironment(): void
    {
        // Arrange
        $host     = 'example.com';
        $tenant   = Mockery::mock(Tenant::class);
        $cacheTtl = 3600;

        $this->application->shouldReceive('environment')
            ->once()
            ->with('local')
            ->andReturn(false);

        $this->request->shouldReceive('getHost')
            ->twice()
            ->andReturn($host);

        $this->request->shouldReceive('header')
            ->twice()
            ->with(TenantHeader::TENANT_DOMAIN->value)
            ->andReturn('');

        $this->config->shouldReceive('get')
            ->once()
            ->with('tenant.tenant_cache.resolver_ttl')
            ->andReturn($cacheTtl);

        $this->cache->shouldReceive('remember')
            ->once()
            ->with($host, $cacheTtl, Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) use ($tenant) {
                return $callback();
            });

        $this->tenantFindService->shouldReceive('findTenantByDomain')
            ->once()
            ->with($host)
            ->andReturn($tenant);

        // Act
        $result = $this->hostResolver->resolveTenant();

        // Assert
        $this->assertSame($tenant, $result);
    }

    #[TestDox('It should throw HttpResponseException when tenant not found')]
    public function testThrowsExceptionWhenTenantNotFound(): void
    {
        // Arrange
        $host = 'example.com';

        $this->application->shouldReceive('environment')
            ->once()
            ->with('local')
            ->andReturn(true);

        $this->request->shouldReceive('getHost')
            ->once()
            ->andReturn($host);

        $this->request->shouldReceive('header')
            ->once()
            ->with(TenantHeader::TENANT_DOMAIN->value)
            ->andReturn('');

        $this->tenantFindService->shouldReceive('findTenantByDomain')
            ->once()
            ->with($host)
            ->andReturnNull();

        $this->frontendService->shouldReceive('redirect')
            ->once()
            ->with('')
            ->andReturn(new RedirectResponse('test'));

        // Act & Assert
        $this->expectException(HttpResponseException::class);
        $this->expectExceptionObject(new HttpResponseException(new RedirectResponse('test')));
        $this->hostResolver->resolveTenant();
    }

    #[TestDox('It should use custom host from header when request is internal')]
    public function testUsesCustomHostFromHeaderWhenRequestIsInternal(): void
    {
        // Arrange
        $defaultHost      = 'example.com';
        $customHost       = 'custom-tenant.example.com';
        $customHostHeader = 'https://' . $customHost;
        $tenant           = Mockery::mock(Tenant::class);

        $this->application->shouldReceive('environment')
            ->once()
            ->with('local')
            ->andReturn(true);

        $this->request->shouldReceive('getHost')
            ->once()
            ->andReturn($defaultHost);

        $this->request->shouldReceive('header')
            ->once()
            ->with(TenantHeader::TENANT_DOMAIN->value)
            ->andReturn($customHostHeader);

        $this->request->headers = Mockery::mock(HeaderBag::class);

        $this->request->server = Mockery::mock(ServerBag::class);

        $this->request->headers->shouldReceive('set')
            ->once()
            ->with('host', $customHost);

        $this->request->server->shouldReceive('set')
            ->once()
            ->with('HTTP_HOST', $customHost);

        $this->requestPrivacyService->shouldReceive('isInternalRequest')
            ->once()
            ->andReturn(true);

        $this->tenantFindService->shouldReceive('findTenantByDomain')
            ->once()
            ->with($customHost)
            ->andReturn($tenant);

        // Act
        $result = $this->hostResolver->resolveTenant();

        // Assert
        $this->assertSame($tenant, $result);
    }

    #[TestDox('It should ignore custom host from header when request is not internal')]
    public function testIgnoresCustomHostFromHeaderWhenRequestIsNotInternal(): void
    {
        // Arrange
        $defaultHost      = 'example.com';
        $customHost       = 'custom-tenant.example.com';
        $customHostHeader = 'https://' . $customHost;
        $tenant           = Mockery::mock(Tenant::class);

        $this->application->shouldReceive('environment')
            ->once()
            ->with('local')
            ->andReturn(true);

        $this->request->shouldReceive('getHost')
            ->once()
            ->andReturn($defaultHost);

        $this->request->shouldReceive('header')
            ->once()
            ->with(TenantHeader::TENANT_DOMAIN->value)
            ->andReturn($customHostHeader);

        $this->requestPrivacyService->shouldReceive('isInternalRequest')
            ->once()
            ->andReturn(false);

        $this->tenantFindService->shouldReceive('findTenantByDomain')
            ->once()
            ->with($defaultHost)
            ->andReturn($tenant);

        // Act
        $result = $this->hostResolver->resolveTenant();

        // Assert
        $this->assertSame($tenant, $result);
    }

    #[TestDox('It should handle invalid custom host URL in header')]
    public function testHandlesInvalidCustomHostUrlInHeader(): void
    {
        // Arrange
        $defaultHost             = 'example.com';
        $invalidCustomHostHeader = 'invalid-url';
        $tenant                  = Mockery::mock(Tenant::class);

        $this->application->shouldReceive('environment')
            ->once()
            ->with('local')
            ->andReturn(true);

        $this->request->shouldReceive('getHost')
            ->once()
            ->andReturn($defaultHost);

        // Properly mock the header method without accessing Request::$headers directly
        $this->request->shouldReceive('header')
            ->once()
            ->with(TenantHeader::TENANT_DOMAIN->value)
            ->andReturn($invalidCustomHostHeader);

        // Since parse_url will return false for invalid URL, no host will be extracted
        // So the request should proceed with the default host

        $this->tenantFindService->shouldReceive('findTenantByDomain')
            ->once()
            ->with($defaultHost)
            ->andReturn($tenant);

        $this->requestPrivacyService->shouldReceive('isInternalRequest')
            ->once()
            ->andReturn(false);

        // Act
        $result = $this->hostResolver->resolveTenant();

        // Assert
        $this->assertSame($tenant, $result);
    }
}
