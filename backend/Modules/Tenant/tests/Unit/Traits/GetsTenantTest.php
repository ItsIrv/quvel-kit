<?php

namespace Modules\Tenant\Tests\Unit\Traits;

use Mockery;
use Mockery\MockInterface;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\Traits\GetsTenant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(GetsTenant::class)]
#[Group('tenant-module')]
#[Group('tenant-traits')]
final class GetsTenantTest extends TestCase
{
    /**
     * @var FindService|MockInterface
     */
    private MockInterface $findService;

    /**
     * @var object Class that uses the GetsTenant trait
     */
    private object $traitUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->tenantContext = Mockery::mock(TenantContext::class);
        $this->findService   = Mockery::mock(FindService::class);

        // Bind mocks to container
        $this->app->instance(TenantContext::class, $this->tenantContext);
        $this->app->instance(FindService::class, $this->findService);

        // Create a class that uses the GetsTenant trait
        $this->traitUser = new class () {
            use GetsTenant {
                getTenant as public;
                getTenantId as public;
                getTenantPublicId as public;
                getTenantFindService as public;
            }
        };
    }

    #[TestDox('It should get tenant from context')]
    public function testGetTenantReturnsFromContext(): void
    {
        // Arrange
        $tenant = Mockery::mock(Tenant::class);

        $this->tenantContext->shouldReceive('get')
            ->once()
            ->andReturn($tenant);

        // Act
        $result = $this->traitUser->getTenant();

        // Assert
        $this->assertSame($tenant, $result);
    }

    #[TestDox('It should get tenant ID from tenant')]
    public function testGetTenantIdReturnsFromTenant(): void
    {
        // Arrange
        // Create a partial mock of the Tenant class
        $tenant = Mockery::mock(Tenant::class);

        // Mock both getAttribute and __get methods
        $tenant->shouldReceive('getAttribute')->with('id')->andReturn(1);

        // Mock the context to return our tenant
        $this->tenantContext->shouldReceive('get')
            ->once()
            ->andReturn($tenant);

        // Act
        $result = $this->traitUser->getTenantId();

        // Assert
        $this->assertEquals(1, $result);
    }

    #[TestDox('It should get tenant public ID from tenant')]
    public function testGetTenantPublicIdReturnsFromTenant(): void
    {
        // Arrange
        // Create a partial mock of the Tenant class
        $tenant = Mockery::mock(Tenant::class);

        // Mock both getAttribute and __get methods
        $tenant->shouldReceive('getAttribute')->with('public_id')->andReturn('test-tenant-id');

        // Mock the context to return our tenant
        $this->tenantContext->shouldReceive('get')
            ->once()
            ->andReturn($tenant);

        // Act
        $result = $this->traitUser->getTenantPublicId();

        // Assert
        $this->assertEquals('test-tenant-id', $result);
    }

    #[TestDox('It should get tenant find service from container')]
    public function testGetTenantFindServiceReturnsFromContainer(): void
    {
        // Act
        $result = $this->traitUser->getTenantFindService();

        // Assert
        $this->assertSame($this->findService, $result);
    }
}
