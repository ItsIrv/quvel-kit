<?php

namespace Modules\Tenant\Services;

use Modules\Tenant\Models\Tenant;
use Illuminate\Support\Facades\Cache;

class TierService
{
    /**
     * Check if the tier system is enabled.
     */
    public function isEnabled(): bool
    {
        return config('tenant.enable_tiers', false);
    }

    /**
     * Check if a tenant has access to a specific feature based on their tier.
     */
    public function hasFeature(Tenant $tenant, string $feature): bool
    {
        // If tiers are disabled, all features are available
        if (!$this->isEnabled()) {
            return true;
        }

        $tier       = $tenant->config?->getTier() ?? 'basic';
        $tierConfig = config("tenant.tiers.{$tier}");

        if (!$tierConfig) {
            return false;
        }

        return in_array($feature, $tierConfig['features'] ?? []);
    }

    /**
     * Check if the current tenant has access to a specific feature.
     */
    public function currentTenantHasFeature(string $feature): bool
    {
        try {
            $tenant = getTenant();
            return $this->hasFeature($tenant, $feature);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all features available for a specific tier.
     */
    public function getTierFeatures(string $tier): array
    {
        return config("tenant.tiers.{$tier}.features", []);
    }

    /**
     * Get the tier configuration.
     */
    public function getTierConfig(string $tier): array
    {
        return config("tenant.tiers.{$tier}", []);
    }

    /**
     * Check if a tier exists.
     */
    public function tierExists(string $tier): bool
    {
        return !empty(config("tenant.tiers.{$tier}"));
    }

    /**
     * Get all available tiers.
     */
    public function getAvailableTiers(): array
    {
        return array_keys(config('tenant.tiers', []));
    }

    /**
     * Compare two tiers.
     * Returns: -1 if tier1 < tier2, 0 if equal, 1 if tier1 > tier2
     */
    public function compareTiers(string $tier1, string $tier2): int
    {
        $tiers  = $this->getAvailableTiers();
        $index1 = array_search($tier1, $tiers);
        $index2 = array_search($tier2, $tiers);

        if ($index1 === false || $index2 === false) {
            return 0;
        }

        return $index1 <=> $index2;
    }

    /**
     * Check if a tenant meets the minimum tier requirement.
     */
    public function meetsMinimumTier(Tenant $tenant, string $minimumTier): bool
    {
        // If tiers are disabled, all tenants meet any requirement
        if (!$this->isEnabled()) {
            return true;
        }

        $tenantTier = $tenant->config?->getTier() ?? 'basic';
        return $this->compareTiers($tenantTier, $minimumTier) >= 0;
    }

    /**
     * Get tier-specific configuration value.
     */
    public function getTierConfigValue(Tenant $tenant, string $key, mixed $default = null): mixed
    {
        $tier = $tenant->config?->getTier() ?? 'basic';

        // Check tier-specific configs
        $tierConfigs = config("tenant.tier_configs.{$tier}", []);

        return $tierConfigs[$key] ?? $default;
    }

    /**
     * Get cached tier information for a tenant.
     */
    public function getCachedTierInfo(Tenant $tenant): array
    {
        $cacheKey = "tenant_tier_{$tenant->id}";

        return Cache::remember($cacheKey, 3600, function () use ($tenant) {
            $tier = $tenant->config?->getTier() ?? 'basic';

            return [
                'tier'        => $tier,
                'features'    => $this->getTierFeatures($tier),
                'description' => config("tenant.tiers.{$tier}.description"),
                'limits'      => $this->getTierLimits($tier),
            ];
        });
    }

    /**
     * Clear cached tier information.
     */
    public function clearTierCache(Tenant $tenant): void
    {
        Cache::forget("tenant_tier_{$tenant->id}");
    }

    /**
     * Get tier-specific limits.
     */
    public function getTierLimits(string $tier): array
    {
        // If tiers are disabled, return unlimited for all resources
        if (!$this->isEnabled()) {
            return [
                'users'               => PHP_INT_MAX,
                'storage'             => PHP_INT_MAX,
                'api_calls_per_hour'  => PHP_INT_MAX,
                'queue_jobs_per_hour' => PHP_INT_MAX,
                'broadcast_connections' => PHP_INT_MAX,
                'file_uploads_per_day' => PHP_INT_MAX,
            ];
        }

        return config("tenant.tier_limits.{$tier}", [
            'users'               => PHP_INT_MAX,
            'storage'             => PHP_INT_MAX,
            'api_calls_per_hour'  => PHP_INT_MAX,
            'queue_jobs_per_hour' => PHP_INT_MAX,
        ]);
    }
}
