<?php

namespace Modules\Tenant\Services;

use Illuminate\Support\Collection;
use Modules\Tenant\Contracts\TenantConfigProviderInterface;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\Enums\TenantConfigVisibility;

/**
 * Registry for tenant configuration providers.
 * Allows modules to register providers that add configuration to tenant responses.
 */
class TenantConfigProviderRegistry
{
    /**
     * @var Collection<int, string> Collection of provider class names
     */
    protected Collection $providerClasses;

    public function __construct()
    {
        $this->providerClasses = collect();
    }

    /**
     * Register a configuration provider class.
     *
     * @param TenantConfigProviderInterface|string $provider
     * @return static
     */
    public function register(TenantConfigProviderInterface|string $provider): static
    {
        // Always store the class name, not an instance
        $providerClass = is_string($provider) ? $provider : get_class($provider);

        $this->providerClasses->push($providerClass);

        return $this;
    }

    /**
     * Apply all registered providers to enhance the tenant configuration.
     *
     * @param Tenant $tenant
     * @param DynamicTenantConfig|null $config
     * @return DynamicTenantConfig
     */
    public function enhance(Tenant $tenant, DynamicTenantConfig|null $config = null): DynamicTenantConfig
    {
        // Start with existing config or empty
        $enhancedConfig = new DynamicTenantConfig();

        // Create provider instances and collect with priorities
        $providers = $this->providerClasses->map(function (string $providerClass) {
            /** @var TenantConfigProviderInterface $provider */
            $provider = app($providerClass);
            return [
                'provider' => $provider,
                'priority' => $provider->priority(),
            ];
        });

        // Sort by priority (higher first)
        $sortedProviders = $providers->sortByDesc('priority');

        // Apply each provider
        foreach ($sortedProviders as $providerData) {
            /** @var TenantConfigProviderInterface $provider */
            $provider   = $providerData['provider'];
            $configData = $provider->getConfig($tenant);

            // Add configuration values
            foreach ($configData['config'] ?? [] as $key => $value) {
                $enhancedConfig->set($key, $value);
            }

            // Add visibility settings
            foreach ($configData['visibility'] ?? [] as $key => $visibility) {
                $visibilityEnum = is_string($visibility)
                    ? TenantConfigVisibility::tryFrom($visibility) ?? TenantConfigVisibility::PRIVATE
                    : $visibility;

                $enhancedConfig->setVisibility($key, $visibilityEnum);
            }
        }

        return $enhancedConfig;
    }

    /**
     * Get all registered provider classes.
     *
     * @return Collection<int, string>
     */
    public function getProviderClasses(): Collection
    {
        return $this->providerClasses;
    }
}
