<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Base configuration pipe with default implementations and utility methods.
 */
abstract class BaseConfigurationPipe implements ConfigurationPipeInterface
{
    /**
     * Default resolve implementation.
     *
     * @param Tenant $tenant The tenant context
     * @param array $tenantConfig The tenant configuration array
     * @return array Empty array by default
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        return [];
    }

    /**
     * Apply resolved internal fields to Laravel config.
     *
     * @param array $resolved Results from resolve() method
     * @param ConfigRepository $config Laravel config repository
     * @param array $mappings Array mapping resolved keys to config paths
     */
    protected function applyInternalFields(array $resolved, ConfigRepository $config, array $mappings): void
    {
        foreach ($mappings as $resolvedKey => $configPath) {
            if (isset($resolved[$resolvedKey])) {
                $config->set($configPath, $resolved[$resolvedKey]);
            }
        }
    }

    /**
     * Filter frontend-safe fields from resolved config.
     *
     * @param array $resolved Results from resolve() method
     * @return array Filtered array with only frontend fields
     */
    protected function getFrontendFields(array $resolved): array
    {
        return array_filter($resolved, function ($key) {
            return !str_starts_with($key, '_internal_');
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * Check if a tenant configuration value is set and not empty.
     *
     * @param array $tenantConfig The tenant configuration array
     * @param string $key The configuration key to check
     * @return bool True if value exists and is not empty
     */
    protected function hasValue(array $tenantConfig, string $key): bool
    {
        return isset($tenantConfig[$key]) && $tenantConfig[$key] !== '' && $tenantConfig[$key] !== null;
    }

    /**
     * Get a configuration value with optional default.
     *
     * @param array $tenantConfig The tenant configuration array
     * @param string $key The configuration key
     * @param mixed $default Default value if key not found
     * @return mixed The configuration value or default
     */
    protected function getValue(array $tenantConfig, string $key, mixed $default = null): mixed
    {
        return $tenantConfig[$key] ?? $default;
    }
}
