<?php

namespace Modules\Tenant\Tests\Unit\Helpers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contexts\TenantContext;
use Modules\Tenant\Enums\TenantConfigVisibility;
use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\FindService;
use Modules\Tenant\Services\TierService;
use Modules\Tenant\Tests\TestCase;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\Covers;
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
     * @var TierService|\Mockery\MockInterface
     */
    private $tierService;

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

        // Mock the TierService
        $this->tierService = $this->mock(TierService::class);
        $this->app->instance(TierService::class, $this->tierService);

        // Note: We don't need to mock TenantContext as it's already set up in the parent TestCase
    }

    #[TestDox('setTenant function sets tenant by ID')]
    #[Covers('setTenant')]
    public function testSetTenantById(): void
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
        $configRepositoryMock->shouldReceive('get')->withAnyArgs()->andReturn('test-value')->zeroOrMoreTimes();
        $configRepositoryMock->shouldReceive('set')->withAnyArgs()->zeroOrMoreTimes();
        $this->app->instance(ConfigRepository::class, $configRepositoryMock);

        // Act
        $result = setTenant($tenantId);

        // Assert
        $this->assertTrue($result);
    }

    #[TestDox('setTenant function sets tenant by domain')]
    #[Covers('setTenant')]
    public function testSetTenantByDomain(): void
    {
        // Arrange
        $domain = 'example.com';

        $this->findService->shouldReceive('findTenantByDomain')
            ->once()
            ->with($domain)
            ->andReturn($this->tenant);

        $tenantContextMock = $this->mock(TenantContext::class);
        $tenantContextMock->shouldReceive('set')
            ->once()
            ->with($this->tenant);
        $this->app->instance(TenantContext::class, $tenantContextMock);

        // Mock config repo
        $configRepositoryMock = $this->mock(ConfigRepository::class);
        $configRepositoryMock->shouldReceive('get')->withAnyArgs()->andReturn('test-value')->zeroOrMoreTimes();
        $configRepositoryMock->shouldReceive('set')->withAnyArgs()->zeroOrMoreTimes();
        $this->app->instance(ConfigRepository::class, $configRepositoryMock);

        // Act
        $result = setTenant($domain);

        // Assert
        $this->assertTrue($result);
    }

    #[TestDox('setTenant function sets tenant by instance')]
    #[Covers('setTenant')]
    public function testSetTenantByInstance(): void
    {
        // Arrange
        $tenant     = new Tenant();
        $tenant->id = 123;

        $tenantContextMock = $this->mock(TenantContext::class);
        $tenantContextMock->shouldReceive('set')
            ->once()
            ->with($tenant);
        $this->app->instance(TenantContext::class, $tenantContextMock);

        // Mock config repo
        $configRepositoryMock = $this->mock(ConfigRepository::class);
        $configRepositoryMock->shouldReceive('get')->withAnyArgs()->andReturn('test-value')->zeroOrMoreTimes();
        $configRepositoryMock->shouldReceive('set')->withAnyArgs()->zeroOrMoreTimes();
        $this->app->instance(ConfigRepository::class, $configRepositoryMock);

        // Act
        $result = setTenant($tenant);

        // Assert
        $this->assertTrue($result);
    }

    #[TestDox('setTenant function throws exception for invalid type')]
    #[Covers('setTenant')]
    public function testSetTenantThrowsExceptionForInvalidType(): void
    {
        // Assert & Act
        $this->expectException(TenantNotFoundException::class);
        $this->expectExceptionMessage('Tenant not found.');

        setTenant([]); // Invalid type
    }

    #[TestDox('setTenant function throws exception when tenant not found')]
    #[Covers('setTenant')]
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

    #[TestDox('setTenantContext function sets context without applying config')]
    #[Covers('setTenantContext')]
    public function testSetTenantContextWithoutApplyingConfig(): void
    {
        // Arrange
        $tenantId = 1;

        $this->findService->shouldReceive('findById')
            ->once()
            ->with($tenantId)
            ->andReturn($this->tenant);

        $tenantContextMock = $this->mock(TenantContext::class);
        $tenantContextMock->shouldReceive('set')
            ->once()
            ->with($this->tenant);
        $this->app->instance(TenantContext::class, $tenantContextMock);

        // ConfigRepository should NOT be called
        $this->configRepository->shouldNotReceive('set');

        // Act
        $result = setTenantContext($tenantId);

        // Assert
        $this->assertTrue($result);
    }

    #[TestDox('getTenant function returns tenant from context')]
    #[Covers('getTenant')]
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

    #[TestDox('getTenantConfig returns entire config when no key specified')]
    #[Covers('getTenantConfig')]
    public function testGetTenantConfigReturnsEntireConfig(): void
    {
        // Arrange
        $config               = new DynamicTenantConfig(['key1' => 'value1', 'key2' => 'value2']);
        $this->tenant->config = $config;

        // Act
        $result = getTenantConfig();

        // Assert
        $this->assertSame($config, $result);
    }

    #[TestDox('getTenantConfig returns specific key value')]
    #[Covers('getTenantConfig')]
    public function testGetTenantConfigReturnsSpecificKey(): void
    {
        // Arrange
        $config               = new DynamicTenantConfig(['key1' => 'value1', 'key2' => 'value2']);
        $this->tenant->config = $config;

        // Act
        $result = getTenantConfig('key1');

        // Assert
        $this->assertEquals('value1', $result);
    }

    #[TestDox('getTenantConfig returns default when key not found')]
    #[Covers('getTenantConfig')]
    public function testGetTenantConfigReturnsDefaultWhenKeyNotFound(): void
    {
        // Arrange
        $config               = new DynamicTenantConfig(['key1' => 'value1']);
        $this->tenant->config = $config;

        // Act
        $result = getTenantConfig('nonexistent', 'default');

        // Assert
        $this->assertEquals('default', $result);
    }

    #[TestDox('setTenantConfig sets config value with visibility')]
    #[Covers('setTenantConfig')]
    public function testSetTenantConfigSetsValueWithVisibility(): void
    {
        // Arrange
        $config               = new DynamicTenantConfig();
        $this->tenant->config = $config;

        // Act
        setTenantConfig('test_key', 'test_value', TenantConfigVisibility::PUBLIC);

        // Assert
        $this->assertEquals('test_value', $this->tenant->config->get('test_key'));
        $this->assertEquals(TenantConfigVisibility::PUBLIC , $this->tenant->config->getVisibility('test_key'));
    }

    #[TestDox('setTenantConfig sets config value with string visibility')]
    #[Covers('setTenantConfig')]
    public function testSetTenantConfigSetsValueWithStringVisibility(): void
    {
        // Arrange
        $config               = new DynamicTenantConfig();
        $this->tenant->config = $config;

        // Act
        setTenantConfig('test_key', 'test_value', 'protected');

        // Assert
        $this->assertEquals('test_value', $this->tenant->config->get('test_key'));
        $this->assertEquals(TenantConfigVisibility::PROTECTED , $this->tenant->config->getVisibility('test_key'));
    }

    #[TestDox('setTenantConfig creates config if not exists')]
    #[Covers('setTenantConfig')]
    public function testSetTenantConfigCreatesConfigIfNotExists(): void
    {
        // Arrange
        $this->tenant->config = null;

        // Act
        setTenantConfig('test_key', 'test_value');

        // Assert
        $this->assertInstanceOf(DynamicTenantConfig::class, $this->tenant->config);
        $this->assertEquals('test_value', $this->tenant->config->get('test_key'));
    }

    #[TestDox('getTenantTier returns tenant tier')]
    #[Covers('getTenantTier')]
    public function testGetTenantTierReturnsTier(): void
    {
        // Arrange
        $config = new DynamicTenantConfig();
        $config->setTier('premium');
        $this->tenant->config = $config;

        // Act
        $result = getTenantTier();

        // Assert
        $this->assertEquals('premium', $result);
    }

    #[TestDox('getTenantTier returns basic when no tier set')]
    #[Covers('getTenantTier')]
    public function testGetTenantTierReturnsBasicWhenNoTier(): void
    {
        // Arrange
        $this->tenant->config = null;

        // Act
        $result = getTenantTier();

        // Assert
        $this->assertEquals('basic', $result);
    }

    #[TestDox('createTenantConfig creates config with parameters')]
    #[Covers('createTenantConfig')]
    public function testCreateTenantConfigCreatesWithParameters(): void
    {
        // Act
        $config = createTenantConfig(
            ['key1' => 'value1'],
            ['key1' => TenantConfigVisibility::PUBLIC],
            'premium',
        );

        // Assert
        $this->assertInstanceOf(DynamicTenantConfig::class, $config);
        $this->assertEquals('value1', $config->get('key1'));
        $this->assertEquals(TenantConfigVisibility::PUBLIC , $config->getVisibility('key1'));
        $this->assertEquals('premium', $config->getTier());
    }

    #[TestDox('tenantHasFeature checks current tenant feature')]
    #[Covers('tenantHasFeature')]
    public function testTenantHasFeatureChecksFeature(): void
    {
        // Arrange
        $this->tierService->shouldReceive('currentTenantHasFeature')
            ->once()
            ->with('test_feature')
            ->andReturn(true);

        // Act
        $result = tenantHasFeature('test_feature');

        // Assert
        $this->assertTrue($result);
    }

    #[TestDox('tenantMeetsMinimumTier checks minimum tier')]
    #[Covers('tenantMeetsMinimumTier')]
    public function testTenantMeetsMinimumTierChecksTier(): void
    {
        // Arrange
        $this->tierService->shouldReceive('meetsMinimumTier')
            ->once()
            ->with($this->tenant, 'premium')
            ->andReturn(true);

        // Act
        $result = tenantMeetsMinimumTier('premium');

        // Assert
        $this->assertTrue($result);
    }

    #[TestDox('tenantMeetsMinimumTier returns false on exception')]
    #[Covers('tenantMeetsMinimumTier')]
    public function testTenantMeetsMinimumTierReturnsFalseOnException(): void
    {
        // Arrange
        $tenantContextMock = $this->mock(TenantContext::class);
        $tenantContextMock->shouldReceive('get')
            ->once()
            ->andThrow(new \Exception('No tenant'));
        $this->app->instance(TenantContext::class, $tenantContextMock);

        // Act
        $result = tenantMeetsMinimumTier('premium');

        // Assert
        $this->assertFalse($result);
    }

    #[TestDox('getTenantFeatures returns features for current tier')]
    #[Covers('getTenantFeatures')]
    public function testGetTenantFeaturesReturnsFeatures(): void
    {
        // Arrange
        $config = new DynamicTenantConfig();
        $config->setTier('premium');
        $this->tenant->config = $config;

        $features = ['feature1', 'feature2', 'feature3'];
        $this->tierService->shouldReceive('getTierFeatures')
            ->once()
            ->with('premium')
            ->andReturn($features);

        // Act
        $result = getTenantFeatures();

        // Assert
        $this->assertEquals($features, $result);
    }

    #[TestDox('getTenantLimits returns limits for current tier')]
    #[Covers('getTenantLimits')]
    public function testGetTenantLimitsReturnsLimits(): void
    {
        // Arrange
        $config = new DynamicTenantConfig();
        $config->setTier('premium');
        $this->tenant->config = $config;

        $limits = ['users' => 100, 'storage' => 10000];
        $this->tierService->shouldReceive('getTierLimits')
            ->once()
            ->with('premium')
            ->andReturn($limits);

        // Act
        $result = getTenantLimits();

        // Assert
        $this->assertEquals($limits, $result);
    }

    #[TestDox('getTenantLimit returns specific limit')]
    #[Covers('getTenantLimit')]
    public function testGetTenantLimitReturnsSpecificLimit(): void
    {
        // Arrange
        $config = new DynamicTenantConfig();
        $config->setTier('premium');
        $this->tenant->config = $config;

        $limits = ['users' => 100, 'storage' => 10000];
        $this->tierService->shouldReceive('getTierLimits')
            ->once()
            ->with('premium')
            ->andReturn($limits);

        // Act
        $result = getTenantLimit('users');

        // Assert
        $this->assertEquals(100, $result);
    }

    #[TestDox('getTenantLimit returns default when limit not found')]
    #[Covers('getTenantLimit')]
    public function testGetTenantLimitReturnsDefaultWhenNotFound(): void
    {
        // Arrange
        $config = new DynamicTenantConfig();
        $config->setTier('premium');
        $this->tenant->config = $config;

        $limits = ['users' => 100];
        $this->tierService->shouldReceive('getTierLimits')
            ->once()
            ->with('premium')
            ->andReturn($limits);

        // Act
        $result = getTenantLimit('nonexistent', 500);

        // Assert
        $this->assertEquals(500, $result);
    }

    #[TestDox('getTenantLimit returns PHP_INT_MAX as default')]
    #[Covers('getTenantLimit')]
    public function testGetTenantLimitReturnsPhpIntMaxAsDefault(): void
    {
        // Arrange
        $config = new DynamicTenantConfig();
        $config->setTier('premium');
        $this->tenant->config = $config;

        $limits = [];
        $this->tierService->shouldReceive('getTierLimits')
            ->once()
            ->with('premium')
            ->andReturn($limits);

        // Act
        $result = getTenantLimit('nonexistent');

        // Assert
        $this->assertEquals(PHP_INT_MAX, $result);
    }
}
