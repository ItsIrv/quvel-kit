<?php

namespace Modules\Tenant\Pipes;

use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Base configuration pipe with default implementations.
 * Provides sensible defaults for the resolve() method.
 */
abstract class BaseConfigurationPipe implements ConfigurationPipeInterface
{
    /**
     * Default resolve implementation.
     * Returns all explicitly set configuration values that this pipe handles.
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        $resolved = [];

        // Include all config values that are explicitly set and handled by this pipe
        foreach ($this->handles() as $key) {
            if (isset($tenantConfig[$key])) {
                $resolved[$key] = $tenantConfig[$key];
            }
        }

        return $resolved;
    }
}