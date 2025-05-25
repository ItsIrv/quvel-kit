<?php

namespace Modules\Tenant\Tests\Unit\Helpers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Mockery;

/**
 * Tests for the helper functions in the Tenant module.
 */
#[Group('tenant-module')]
#[Group('tenant-helpers')]
final class HelpersTest extends TestCase
{
    /**
     * @var FindService|\Mockery\MockInterface
     */
    private $findService;

    /**
     * @var ConfigRepository|\Mockery\MockInterface
     */
    private $configRepository;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock the FindService
        $this->findService = $this->mock(FindService::class);
        $this->app->instance(FindService::class, $this->findService);

        // Mock the ConfigRepository
        $this->configRepository = $this->mock(ConfigRepository::class);
        $this->app->instance(ConfigRepository::class, $this->configRepository);

        // Note: We don't need to mock TenantContext as it's already set up in the parent TestCase
    }

    #[TestDox('setTenant function sets tenant in context and applies config')]
    public function testSetTenantSetsContextAndAppliesConfig(): void
    {
        // Arrange
        $tenantId = 1;

        // Use the tenant from the parent TestCase
        $this->findService->shouldReceive('findById')
            ->once()
            ->with($tenantId)
            ->andReturn($this->tenant);

        // We need to mock the TenantContext for this specific test
        $tenantContextMock = $this->mock(TenantContext::class);
        $tenantContextMock->shouldReceive('set')
            ->once()
            ->with($this->tenant);
        $this->app->instance(TenantContext::class, $tenantContextMock);

        // Mock the ConfigRepository to avoid actual config changes
        $configRepositoryMock = $this->mock(ConfigRepository::class);
        // Set up expectations for all config settings that will be applied
        $configRepositoryMock->shouldReceive('set')->withAnyArgs()->zeroOrMoreTimes();
        $this->app->instance(ConfigRepository::class, $configRepositoryMock);

        // Act
        setTenant($tenantId);

        // No need to assert as Mockery will verify the expectations
    }

    #[TestDox('setTenant function throws exception when tenant not found')]
    public function testSetTenantThrowsExceptionWhenTenantNotFound(): void
    {
        // Arrange
        $tenantId = 999; // Non-existent tenant ID

        $this->findService->shouldReceive('findById')
            ->once()
            ->with($tenantId)
            ->andReturn(null);

        // Assert & Act
        $this->expectException(TenantNotFoundException::class);
        $this->expectExceptionMessage('Tenant not found');

        setTenant($tenantId);
    }

    #[TestDox('getTenant function returns tenant from context')]
    public function testGetTenantReturnsTenantFromContext(): void
    {
        // Arrange - Use the tenant and context from the parent TestCase
        // We need to mock the TenantContext for this specific test
        $tenantContextMock = $this->mock(TenantContext::class);
        $tenantContextMock->shouldReceive('get')
            ->once()
            ->andReturn($this->tenant);
        $this->app->instance(TenantContext::class, $tenantContextMock);

        // Act
        $result = getTenant();

        // Assert
        $this->assertSame($this->tenant, $result);
    }
}
