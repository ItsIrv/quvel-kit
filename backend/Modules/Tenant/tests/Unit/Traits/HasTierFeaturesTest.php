<?php

namespace Modules\Tenant\Tests\Unit\Traits;

use Illuminate\Support\Facades\Config;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TierService;
use Modules\Tenant\Traits\HasTierFeatures;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(HasTierFeatures::class)]
#[Group('tenant-module')]
#[Group('tenant-traits')]
final class HasTierFeaturesTest extends TestCase
{
    protected Tenant $tenantModel;
    private TierService $tierService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant instance that uses the trait
        $this->tenantModel = new Tenant();

        // Mock the TierService
        $this->tierService = $this->createMock(TierService::class);
        $this->app->instance(TierService::class, $this->tierService);
    }

    #[TestDox('Should check if tenant has feature')]
    public function testHasFeature(): void
    {
        // Arrange
        $feature = 'test_feature';
        $this->tierService->expects($this->once())
            ->method('hasFeature')
            ->with($this->tenantModel, $feature)
            ->willReturn(true);

        // Act
        $result = $this->tenantModel->hasFeature($feature);

        // Assert
        $this->assertTrue($result);
    }

    #[TestDox('Should return false when tenant does not have feature')]
    public function testHasFeatureReturnsFalse(): void
    {
        // Arrange
        $feature = 'missing_feature';
        $this->tierService->expects($this->once())
            ->method('hasFeature')
            ->with($this->tenantModel, $feature)
            ->willReturn(false);

        // Act
        $result = $this->tenantModel->hasFeature($feature);

        // Assert
        $this->assertFalse($result);
    }

    #[TestDox('Should check if tenant meets minimum tier')]
    public function testMeetsMinimumTier(): void
    {
        // Arrange
        $minimumTier = 'premium';
        $this->tierService->expects($this->once())
            ->method('meetsMinimumTier')
            ->with($this->tenantModel, $minimumTier)
            ->willReturn(true);

        // Act
        $result = $this->tenantModel->meetsMinimumTier($minimumTier);

        // Assert
        $this->assertTrue($result);
    }

    #[TestDox('Should return false when tenant does not meet minimum tier')]
    public function testMeetsMinimumTierReturnsFalse(): void
    {
        // Arrange
        $minimumTier = 'enterprise';
        $this->tierService->expects($this->once())
            ->method('meetsMinimumTier')
            ->with($this->tenantModel, $minimumTier)
            ->willReturn(false);

        // Act
        $result = $this->tenantModel->meetsMinimumTier($minimumTier);

        // Assert
        $this->assertFalse($result);
    }

    #[TestDox('Should get tier features when tenant has tier')]
    public function testGetTierFeaturesWithTier(): void
    {
        // Arrange
        $config = new DynamicTenantConfig();
        $config->setTier('premium');
        $this->tenantModel->config = $config;

        $expectedFeatures = ['feature1', 'feature2', 'feature3'];
        $this->tierService->expects($this->once())
            ->method('getTierFeatures')
            ->with('premium')
            ->willReturn($expectedFeatures);

        // Act
        $result = $this->tenantModel->getTierFeatures();

        // Assert
        $this->assertEquals($expectedFeatures, $result);
    }

    #[TestDox('Should get tier features with default tier when no tier set')]
    public function testGetTierFeaturesWithDefaultTier(): void
    {
        // Arrange
        $this->tenantModel->config = null;

        $expectedFeatures = ['basic_feature1', 'basic_feature2'];
        $this->tierService->expects($this->once())
            ->method('getTierFeatures')
            ->with('basic')
            ->willReturn($expectedFeatures);

        // Act
        $result = $this->tenantModel->getTierFeatures();

        // Assert
        $this->assertEquals($expectedFeatures, $result);
    }

    #[TestDox('Should get tier config value')]
    public function testGetTierConfig(): void
    {
        // Arrange
        $key           = 'max_users';
        $expectedValue = 100;

        $this->tierService->expects($this->once())
            ->method('getTierConfigValue')
            ->with($this->tenantModel, $key, null)
            ->willReturn($expectedValue);

        // Act
        $result = $this->tenantModel->getTierConfig($key);

        // Assert
        $this->assertEquals($expectedValue, $result);
    }

    #[TestDox('Should get tier config value with default')]
    public function testGetTierConfigWithDefault(): void
    {
        // Arrange
        $key     = 'non_existent_key';
        $default = 'default_value';

        $this->tierService->expects($this->once())
            ->method('getTierConfigValue')
            ->with($this->tenantModel, $key, $default)
            ->willReturn($default);

        // Act
        $result = $this->tenantModel->getTierConfig($key, $default);

        // Assert
        $this->assertEquals($default, $result);
    }

    #[TestDox('Should get tier limits when tenant has tier')]
    public function testGetTierLimitsWithTier(): void
    {
        // Arrange
        $config = new DynamicTenantConfig();
        $config->setTier('premium');
        $this->tenantModel->config = $config;

        $expectedLimits = [
            'users'              => 100,
            'storage'            => 10000,
            'api_calls_per_hour' => 1000,
        ];

        $this->tierService->expects($this->once())
            ->method('getTierLimits')
            ->with('premium')
            ->willReturn($expectedLimits);

        // Act
        $result = $this->tenantModel->getTierLimits();

        // Assert
        $this->assertEquals($expectedLimits, $result);
    }

    #[TestDox('Should get tier limits with default tier when no tier set')]
    public function testGetTierLimitsWithDefaultTier(): void
    {
        // Arrange
        $this->tenantModel->config = null;

        $expectedLimits = [
            'users'              => 10,
            'storage'            => 1000,
            'api_calls_per_hour' => 100,
        ];

        $this->tierService->expects($this->once())
            ->method('getTierLimits')
            ->with('basic')
            ->willReturn($expectedLimits);

        // Act
        $result = $this->tenantModel->getTierLimits();

        // Assert
        $this->assertEquals($expectedLimits, $result);
    }

    #[TestDox('Should check if limit has been reached when tiers enabled')]
    public function testHasReachedLimitWhenTiersEnabled(): void
    {
        // Arrange
        Config::set('tenant.enable_tiers', true);

        $config = new DynamicTenantConfig();
        $config->setTier('basic');
        $this->tenantModel->config = $config;

        $limits = ['users' => 10, 'storage' => 1000];

        $this->tierService->expects($this->exactly(3))
            ->method('getTierLimits')
            ->with('basic')
            ->willReturn($limits);

        // Act & Assert
        $this->assertTrue($this->tenantModel->hasReachedLimit('users', 10)); // Equal to limit
        $this->assertTrue($this->tenantModel->hasReachedLimit('users', 15)); // Exceeds limit
        $this->assertFalse($this->tenantModel->hasReachedLimit('users', 5)); // Below limit
    }

    #[TestDox('Should always return false when tiers disabled')]
    public function testHasReachedLimitWhenTiersDisabled(): void
    {
        // Arrange
        Config::set('tenant.enable_tiers', false);

        // Act & Assert
        $this->assertFalse($this->tenantModel->hasReachedLimit('users', PHP_INT_MAX));
        $this->assertFalse($this->tenantModel->hasReachedLimit('any_limit', 999999));
    }

    #[TestDox('Should use PHP_INT_MAX when limit key not found')]
    public function testHasReachedLimitWithUnknownLimitKey(): void
    {
        // Arrange
        Config::set('tenant.enable_tiers', true);

        $config = new DynamicTenantConfig();
        $config->setTier('basic');
        $this->tenantModel->config = $config;

        $limits = ['users' => 10]; // 'unknown_limit' not in array

        $this->tierService->expects($this->once())
            ->method('getTierLimits')
            ->with('basic')
            ->willReturn($limits);

        // Act & Assert
        $this->assertFalse($this->tenantModel->hasReachedLimit('unknown_limit', 1000000)); // Should use PHP_INT_MAX default
    }

    #[TestDox('Should get cached tier info')]
    public function testGetCachedTierInfo(): void
    {
        // Arrange
        $expectedInfo = [
            'tier'        => 'premium',
            'features'    => ['feature1', 'feature2'],
            'description' => 'Premium tier',
            'limits'      => ['users' => 100],
        ];

        $this->tierService->expects($this->once())
            ->method('getCachedTierInfo')
            ->with($this->tenantModel)
            ->willReturn($expectedInfo);

        // Act
        $result = $this->tenantModel->getCachedTierInfo();

        // Assert
        $this->assertEquals($expectedInfo, $result);
    }

    #[TestDox('Should work with multiple tenants independently')]
    public function testMultipleTenantsIndependently(): void
    {
        // Arrange
        $tenant1 = new Tenant();
        $config1 = new DynamicTenantConfig();
        $config1->setTier('basic');
        $tenant1->config = $config1;

        $tenant2 = new Tenant();
        $config2 = new DynamicTenantConfig();
        $config2->setTier('premium');
        $tenant2->config = $config2;

        $this->tierService->expects($this->exactly(2))
            ->method('hasFeature')
            ->willReturnCallback(function ($tenant, $feature) {
                if ($tenant->config->getTier() === 'basic' && $feature === 'basic_feature') {
                    return true;
                }
                if ($tenant->config->getTier() === 'premium' && $feature === 'premium_feature') {
                    return true;
                }
                return false;
            });

        // Act & Assert
        $this->assertTrue($tenant1->hasFeature('basic_feature'));
        $this->assertTrue($tenant2->hasFeature('premium_feature'));
    }

    #[TestDox('Should handle null config gracefully')]
    public function testHandlesNullConfigGracefully(): void
    {
        // Arrange
        $this->tenantModel->config = null;

        $this->tierService->expects($this->once())
            ->method('getTierFeatures')
            ->with('basic') // Should default to 'basic'
            ->willReturn(['default_feature']);

        // Act
        $result = $this->tenantModel->getTierFeatures();

        // Assert
        $this->assertEquals(['default_feature'], $result);
    }

    #[TestDox('Should return correct boolean for hasReachedLimit edge cases')]
    public function testHasReachedLimitEdgeCases(): void
    {
        // Arrange
        Config::set('tenant.enable_tiers', true);

        $config = new DynamicTenantConfig();
        $config->setTier('basic');
        $this->tenantModel->config = $config;

        $limits = ['users' => 0, 'storage' => -1]; // Edge case limits

        $this->tierService->expects($this->exactly(3))
            ->method('getTierLimits')
            ->with('basic')
            ->willReturn($limits);

        // Act & Assert
        $this->assertTrue($this->tenantModel->hasReachedLimit('users', 0)); // 0 >= 0 is true
        $this->assertTrue($this->tenantModel->hasReachedLimit('storage', -1)); // -1 >= -1 is true
        $this->assertTrue($this->tenantModel->hasReachedLimit('storage', 0)); // 0 >= -1 is true
    }
}
