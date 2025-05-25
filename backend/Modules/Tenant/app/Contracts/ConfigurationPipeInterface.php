<?php

namespace Modules\Tenant\Contracts;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Models\Tenant;

/**
 * Interface for tenant configuration pipes.
 * Each pipe can apply specific configuration overrides for a tenant.
 */
interface ConfigurationPipeInterface
{
    /**
     * Apply configuration for the given tenant.
     *
     * @param Tenant $tenant The tenant to apply configuration for
     * @param ConfigRepository $config The Laravel config repository
     * @param array $tenantConfig The tenant's configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed;

    /**
     * Get the configuration keys this pipe handles.
     * This helps document what configurations each pipe manages.
     *
     * @return array<string>
     */
    public function handles(): array;

    /**
     * Get the priority for this pipe (higher runs first).
     *
     * @return int
     */
    public function priority(): int;
}
