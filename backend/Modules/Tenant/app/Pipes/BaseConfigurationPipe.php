<?php

namespace Modules\Tenant\Pipes;

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
     * @return array ['values' => array, 'visibility' => array] Empty by default
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        return ['values' => [], 'visibility' => []];
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
