<?php

namespace Modules\Tenant\Tests\Unit\Actions;

use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Mockery;
use Mockery\MockInterface;
use Modules\Tenant\Actions\TenantsDump;
use Modules\Tenant\Http\Resources\TenantDumpResource;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\FindService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(TenantsDump::class)]
#[Group('tenant-module')]
#[Group('tenant-actions')]
final class TenantsDumpTest extends TestCase
{
    /**
     * @var FindService|MockInterface
     */
    protected FindService $tenantFindService;

    /**
     * @var CacheRepository|MockInterface
     */
    protected CacheRepository $cache;

    /**
     * @var ConfigRepository|MockInterface
     */
    protected ConfigRepository $config;

    /**
     * @var Application|MockInterface
     */
    private MockInterface $appMock;

    /**
     * @var TenantsDump
     */
    protected TenantsDump $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantFindService = Mockery::mock(FindService::class);
        $this->cache             = Mockery::mock(CacheRepository::class);
        $this->config            = Mockery::mock(ConfigRepository::class);
        $this->appMock           = Mockery::mock(Application::class);

        $this->action = new TenantsDump();
    }

    #[TestDox('It should fetch tenants from cache when in non-local environment')]
    public function testFetchesTenantsFromCacheInNonLocalEnvironment(): void
    {
        // Arrange
        $cachedTenants = new Collection([
            new Tenant(['id' => 1, 'name' => 'Tenant A', 'domain' => 'tenant-a.example.com']),
            new Tenant(['id' => 2, 'name' => 'Tenant B', 'domain' => 'tenant-b.example.com']),
        ]);

        $this->appMock->shouldReceive('environment')
            ->once()
            ->with('local')
            ->andReturn(false);

        $this->config->shouldReceive('get')
            ->once()
            ->with('tenant.tenant_cache.cache_ttl')
            ->andReturn(3600);

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('tenants', 3600, Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) use ($cachedTenants) {
                return $cachedTenants;
            });

        // Act
        $result = ($this->action)(
            $this->tenantFindService,
            $this->cache,
            $this->config,
            $this->appMock
        );

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(2, $result->count());
    }

    #[TestDox('It should fetch tenants from database in local environment')]
    public function testFetchesTenantsFromDatabaseInLocalEnvironment(): void
    {
        // Arrange
        $freshTenants = new Collection([
            new Tenant(['id' => 1, 'name' => 'Tenant A', 'domain' => 'tenant-a.example.com']),
            new Tenant(['id' => 2, 'name' => 'Tenant B', 'domain' => 'tenant-b.example.com']),
        ]);

        $this->appMock->shouldReceive('environment')
            ->once()
            ->with('local')
            ->andReturn(true);

        $this->tenantFindService->shouldReceive('findAll')
            ->once()
            ->andReturn($freshTenants);

        // Act
        $result = ($this->action)(
            $this->tenantFindService,
            $this->cache,
            $this->config,
            $this->appMock
        );

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(2, $result->count());
    }

    #[TestDox('It should fetch tenants from database and cache them in non-local environment')]
    public function testFetchesTenantsFromDatabaseAndCachesThemInNonLocalEnvironment(): void
    {
        // Arrange
        $freshTenants = new Collection([
            new Tenant(['id' => 1, 'name' => 'Tenant A', 'domain' => 'tenant-a.example.com']),
            new Tenant(['id' => 2, 'name' => 'Tenant B', 'domain' => 'tenant-b.example.com']),
        ]);

        $this->appMock->shouldReceive('environment')
            ->once()
            ->with('local')
            ->andReturn(false);

        $this->config->shouldReceive('get')
            ->once()
            ->with('tenant.tenant_cache.cache_ttl')
            ->andReturn(3600);

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('tenants', 3600, Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) use ($freshTenants) {
                return $callback();
            });

        $this->tenantFindService->shouldReceive('findAll')
            ->once()
            ->andReturn($freshTenants);

        // Act
        $result = ($this->action)(
            $this->tenantFindService,
            $this->cache,
            $this->config,
            $this->appMock
        );

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(2, $result->count());
    }

    #[TestDox('It should return empty collection when no tenants found')]
    public function testReturnsEmptyCollectionWhenNoTenantsFound(): void
    {
        // Arrange
        $emptyCollection = new Collection([]);

        $this->appMock->shouldReceive('environment')
            ->once()
            ->with('local')
            ->andReturn(false);

        $this->config->shouldReceive('get')
            ->once()
            ->with('tenant.tenant_cache.cache_ttl')
            ->andReturn(3600);

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('tenants', 3600, Mockery::type('Closure'))
            ->andReturnUsing(function ($key, $ttl, $callback) use ($emptyCollection) {
                return $callback();
            });

        $this->tenantFindService->shouldReceive('findAll')
            ->once()
            ->andReturn($emptyCollection);

        // Act
        $result = ($this->action)(
            $this->tenantFindService,
            $this->cache,
            $this->config,
            $this->appMock
        );

        // Assert
        $this->assertInstanceOf(AnonymousResourceCollection::class, $result);
        $this->assertEquals(0, $result->count());
    }
}
