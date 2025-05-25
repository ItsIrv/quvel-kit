<?php

namespace Modules\Tenant\Traits;

use Modules\Tenant\Services\TierService;

trait HasTierFeatures
{
    /**
     * Check if the tenant has access to a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return app(TierService::class)->hasFeature($this, $feature);
    }

    /**
     * Check if the tenant meets a minimum tier requirement.
     */
    public function meetsMinimumTier(string $minimumTier): bool
    {
        return app(TierService::class)->meetsMinimumTier($this, $minimumTier);
    }

    /**
     * Get all features available for this tenant's tier.
     */
    public function getTierFeatures(): array
    {
        $tier = $this->config?->getTier() ?? 'basic';
        return app(TierService::class)->getTierFeatures($tier);
    }

    /**
     * Get tier-specific configuration value.
     */
    public function getTierConfig(string $key, mixed $default = null): mixed
    {
        return app(TierService::class)->getTierConfigValue($this, $key, $default);
    }

    /**
     * Get tier limits for this tenant.
     */
    public function getTierLimits(): array
    {
        $tier = $this->config?->getTier() ?? 'basic';
        return app(TierService::class)->getTierLimits($tier);
    }

    /**
     * Check if a specific limit has been reached.
     */
    public function hasReachedLimit(string $limitKey, int $currentUsage): bool
    {
        // If tiers are disabled, no limits are enforced
        if (!config('tenant.enable_tiers', false)) {
            return false;
        }

        $limits = $this->getTierLimits();
        $limit = $limits[$limitKey] ?? PHP_INT_MAX;

        return $currentUsage >= $limit;
    }

    /**
     * Get cached tier information.
     */
    public function getCachedTierInfo(): array
    {
        return app(TierService::class)->getCachedTierInfo($this);
    }
}
