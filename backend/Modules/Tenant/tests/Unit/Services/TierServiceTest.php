<?php

namespace Modules\Tenant\Tests\Unit\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Services\TierService;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;

#[CoversClass(TierService::class)]
#[Group('tenant-module')]
#[Group('tenant-services')]
final class TierServiceTest extends TestCase
{
    private TierService $tierService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tierService = new TierService();
        
        // Set up default tier configuration for testing
        Config::set('tenant.tiers', [
            'basic' => [
                'features' => ['feature1', 'feature2'],
                'description' => 'Basic tier'
            ],
            'standard' => [
                'features' => ['feature1', 'feature2', 'feature3'],
                'description' => 'Standard tier'
            ],
            'premium' => [
                'features' => ['feature1', 'feature2', 'feature3', 'feature4'],
                'description' => 'Premium tier'
            ],
            'enterprise' => [
                'features' => ['feature1', 'feature2', 'feature3', 'feature4', 'feature5'],
                'description' => 'Enterprise tier'
            ]
        ]);

        Config::set('tenant.tier_limits', [
            'basic' => [
                'users' => 10,
                'storage' => 1000,
                'api_calls_per_hour' => 100
            ],
            'premium' => [
                'users' => 100,
                'storage' => 10000,
                'api_calls_per_hour' => 1000
            ]
        ]);

        Config::set('tenant.tier_configs', [
            'basic' => [
                'max_uploads' => 5,
                'api_timeout' => 30
            ],
            'premium' => [
                'max_uploads' => 50,
                'api_timeout' => 60
            ]
        ]);
    }

    #[TestDox('Should return true when tiers are enabled')]
    public function testIsEnabledWhenTiersEnabled(): void
    {
        Config::set('tenant.enable_tiers', true);

        $this->assertTrue($this->tierService->isEnabled());
    }

    #[TestDox('Should return false when tiers are disabled')]
    public function testIsEnabledWhenTiersDisabled(): void
    {
        Config::set('tenant.enable_tiers', false);

        $this->assertFalse($this->tierService->isEnabled());
    }

    #[TestDox('Should return true for all features when tiers disabled')]
    public function testHasFeatureWhenTiersDisabled(): void
    {
        Config::set('tenant.enable_tiers', false);
        
        $tenant = new Tenant();
        
        $this->assertTrue($this->tierService->hasFeature($tenant, 'any_feature'));
        $this->assertTrue($this->tierService->hasFeature($tenant, 'non_existent_feature'));
    }

    #[TestDox('Should return true when tenant has feature in their tier')]
    public function testHasFeatureWhenTenantHasFeature(): void
    {
        Config::set('tenant.enable_tiers', true);
        
        $config = new DynamicTenantConfig();
        $config->setTier('standard');
        
        $tenant = new Tenant();
        $tenant->config = $config;
        
        $this->assertTrue($this->tierService->hasFeature($tenant, 'feature3'));
    }

    #[TestDox('Should return false when tenant does not have feature in their tier')]
    public function testHasFeatureWhenTenantDoesNotHaveFeature(): void
    {
        Config::set('tenant.enable_tiers', true);
        
        $config = new DynamicTenantConfig();
        $config->setTier('basic');
        
        $tenant = new Tenant();
        $tenant->config = $config;
        
        $this->assertFalse($this->tierService->hasFeature($tenant, 'feature4'));
    }

    #[TestDox('Should return false when tenant has unknown tier')]
    public function testHasFeatureWithUnknownTier(): void
    {
        Config::set('tenant.enable_tiers', true);
        
        $config = new DynamicTenantConfig();
        $config->setTier('unknown_tier');
        
        $tenant = new Tenant();
        $tenant->config = $config;
        
        $this->assertFalse($this->tierService->hasFeature($tenant, 'feature1'));
    }

    #[TestDox('Should default to basic tier when tenant has no tier set')]
    public function testHasFeatureWithNoTierSet(): void
    {
        Config::set('tenant.enable_tiers', true);
        
        $tenant = new Tenant();
        
        $this->assertTrue($this->tierService->hasFeature($tenant, 'feature1'));
        $this->assertFalse($this->tierService->hasFeature($tenant, 'feature4'));
    }

    #[TestDox('Should return correct result when current tenant context exists')]
    public function testCurrentTenantHasFeatureWithExistingContext(): void
    {
        Config::set('tenant.enable_tiers', true);
        
        // There is a tenant context from TestCase setup, should return appropriate result
        // Basic tier has feature1 but not feature5
        $this->assertTrue($this->tierService->currentTenantHasFeature('feature1'));
        $this->assertFalse($this->tierService->currentTenantHasFeature('feature5'));
    }

    #[TestDox('Should get features for specific tier')]
    public function testGetTierFeatures(): void
    {
        $features = $this->tierService->getTierFeatures('standard');
        
        $this->assertEquals(['feature1', 'feature2', 'feature3'], $features);
    }

    #[TestDox('Should return empty array for unknown tier features')]
    public function testGetTierFeaturesForUnknownTier(): void
    {
        $features = $this->tierService->getTierFeatures('unknown');
        
        $this->assertEquals([], $features);
    }

    #[TestDox('Should get tier configuration')]
    public function testGetTierConfig(): void
    {
        $config = $this->tierService->getTierConfig('basic');
        
        $this->assertEquals([
            'features' => ['feature1', 'feature2'],
            'description' => 'Basic tier'
        ], $config);
    }

    #[TestDox('Should return empty array for unknown tier config')]
    public function testGetTierConfigForUnknownTier(): void
    {
        $config = $this->tierService->getTierConfig('unknown');
        
        $this->assertEquals([], $config);
    }

    #[TestDox('Should check if tier exists')]
    public function testTierExists(): void
    {
        $this->assertTrue($this->tierService->tierExists('basic'));
        $this->assertTrue($this->tierService->tierExists('premium'));
        $this->assertFalse($this->tierService->tierExists('unknown'));
    }

    #[TestDox('Should get all available tiers')]
    public function testGetAvailableTiers(): void
    {
        $tiers = $this->tierService->getAvailableTiers();
        
        $this->assertEquals(['basic', 'standard', 'premium', 'enterprise'], $tiers);
    }

    #[TestDox('Should compare tiers correctly')]
    public function testCompareTiers(): void
    {
        $this->assertEquals(-1, $this->tierService->compareTiers('basic', 'standard'));
        $this->assertEquals(0, $this->tierService->compareTiers('premium', 'premium'));
        $this->assertEquals(1, $this->tierService->compareTiers('enterprise', 'basic'));
    }

    #[TestDox('Should return 0 when comparing unknown tiers')]
    public function testCompareTiersWithUnknownTiers(): void
    {
        $this->assertEquals(0, $this->tierService->compareTiers('unknown1', 'unknown2'));
        $this->assertEquals(0, $this->tierService->compareTiers('basic', 'unknown'));
    }

    #[TestDox('Should return true when tiers disabled for minimum tier check')]
    public function testMeetsMinimumTierWhenTiersDisabled(): void
    {
        Config::set('tenant.enable_tiers', false);
        
        $tenant = new Tenant();
        
        $this->assertTrue($this->tierService->meetsMinimumTier($tenant, 'enterprise'));
    }

    #[TestDox('Should check if tenant meets minimum tier requirement')]
    public function testMeetsMinimumTier(): void
    {
        Config::set('tenant.enable_tiers', true);
        
        $config = new DynamicTenantConfig();
        $config->setTier('premium');
        
        $tenant = new Tenant();
        $tenant->config = $config;
        
        $this->assertTrue($this->tierService->meetsMinimumTier($tenant, 'basic'));
        $this->assertTrue($this->tierService->meetsMinimumTier($tenant, 'premium'));
        $this->assertFalse($this->tierService->meetsMinimumTier($tenant, 'enterprise'));
    }

    #[TestDox('Should get tier-specific config value')]
    public function testGetTierConfigValue(): void
    {
        $config = new DynamicTenantConfig();
        $config->setTier('basic');
        
        $tenant = new Tenant();
        $tenant->config = $config;
        
        $this->assertEquals(5, $this->tierService->getTierConfigValue($tenant, 'max_uploads'));
        $this->assertEquals('default', $this->tierService->getTierConfigValue($tenant, 'unknown_key', 'default'));
    }

    #[TestDox('Should get cached tier information')]
    public function testGetCachedTierInfo(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('tenant_tier_1', 3600, \Closure::class)
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $config = new DynamicTenantConfig();
        $config->setTier('premium');
        
        $tenant = new Tenant();
        $tenant->id = 1;
        $tenant->config = $config;
        
        $tierInfo = $this->tierService->getCachedTierInfo($tenant);
        
        $this->assertEquals('premium', $tierInfo['tier']);
        $this->assertEquals(['feature1', 'feature2', 'feature3', 'feature4'], $tierInfo['features']);
        $this->assertEquals('Premium tier', $tierInfo['description']);
        $this->assertArrayHasKey('limits', $tierInfo);
    }

    #[TestDox('Should clear tier cache')]
    public function testClearTierCache(): void
    {
        Cache::shouldReceive('forget')
            ->once()
            ->with('tenant_tier_1');

        $tenant = new Tenant();
        $tenant->id = 1;
        
        $this->tierService->clearTierCache($tenant);
    }

    #[TestDox('Should return unlimited limits when tiers disabled')]
    public function testGetTierLimitsWhenTiersDisabled(): void
    {
        Config::set('tenant.enable_tiers', false);
        
        $limits = $this->tierService->getTierLimits('basic');
        
        $this->assertEquals(PHP_INT_MAX, $limits['users']);
        $this->assertEquals(PHP_INT_MAX, $limits['storage']);
        $this->assertEquals(PHP_INT_MAX, $limits['api_calls_per_hour']);
    }

    #[TestDox('Should get tier-specific limits')]
    public function testGetTierLimits(): void
    {
        Config::set('tenant.enable_tiers', true);
        
        $limits = $this->tierService->getTierLimits('basic');
        
        $this->assertEquals(10, $limits['users']);
        $this->assertEquals(1000, $limits['storage']);
        $this->assertEquals(100, $limits['api_calls_per_hour']);
    }

    #[TestDox('Should return default limits for unknown tier')]
    public function testGetTierLimitsForUnknownTier(): void
    {
        Config::set('tenant.enable_tiers', true);
        
        $limits = $this->tierService->getTierLimits('unknown');
        
        $this->assertEquals(PHP_INT_MAX, $limits['users']);
        $this->assertEquals(PHP_INT_MAX, $limits['storage']);
        $this->assertEquals(PHP_INT_MAX, $limits['api_calls_per_hour']);
        $this->assertEquals(PHP_INT_MAX, $limits['queue_jobs_per_hour']);
    }
}