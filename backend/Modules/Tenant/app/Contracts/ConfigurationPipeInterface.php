<?php

namespace Modules\Tenant\Contracts;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;

/**
 * Configuration pipe interface for tenant-specific Laravel configuration.
 */
interface ConfigurationPipeInterface
{
    /**
     * Calculate configuration values without side effects.
     *
     * @param Tenant $tenant The tenant context for resolution
     * @param array<string, mixed> $tenantConfig The merged tenant configuration array
     * @return array<string, mixed> ['values' => array, 'visibility' => array] Resolved values and their visibility
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array;

    /**
     * Apply configuration changes to Laravel config repository.
     *
     * @param Tenant $tenant The tenant context
     * @param ConfigRepository $config Laravel config repository to modify
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed Result of calling $next()
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed;
}
