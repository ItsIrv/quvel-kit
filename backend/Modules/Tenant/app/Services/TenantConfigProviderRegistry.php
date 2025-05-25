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
     * @var Collection<int, TenantConfigProviderInterface>
     */
    protected Collection $providers;

    public function __construct()
    {
        $this->providers = collect();
    }

    /**
     * Register a configuration provider.
     *
     * @param TenantConfigProviderInterface|string $provider
     * @return static
     */
    public function register(TenantConfigProviderInterface|string $provider): static
    {
        if (is_string($provider)) {
            $provider = app($provider);
        }

        $this->providers->push($provider);

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
        // Configs are provided by providers, so we start with an empty config
        $enhancedConfig = new DynamicTenantConfig();

        // Sort providers by priority (higher first)
        $sortedProviders = $this->providers->sortByDesc(
            fn (TenantConfigProviderInterface $provider) => $provider->priority()
        );

        // Apply each provider
        foreach ($sortedProviders as $provider) {
            $providerData = $provider->getConfig($tenant);

            // Add configuration values
            foreach ($providerData['config'] ?? [] as $key => $value) {
                $enhancedConfig->set($key, $value);
            }

            // Add visibility settings
            foreach ($providerData['visibility'] ?? [] as $key => $visibility) {
                $visibilityEnum = is_string($visibility)
                    ? TenantConfigVisibility::tryFrom($visibility) ?? TenantConfigVisibility::PRIVATE
                    : $visibility;

                $enhancedConfig->setVisibility($key, $visibilityEnum);
            }
        }

        return $enhancedConfig;
    }

    /**
     * Get all registered providers.
     *
     * @return Collection<int, TenantConfigProviderInterface>
     */
    public function getProviders(): Collection
    {
        return $this->providers;
    }
}
