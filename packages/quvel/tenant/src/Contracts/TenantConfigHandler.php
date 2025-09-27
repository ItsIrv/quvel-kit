<?php

declare(strict_types=1);

namespace Quvel\Tenant\Contracts;

use Illuminate\Config\Repository as ConfigRepository;
use Quvel\Tenant\Models\Tenant;

/**
 * Base class for tenant configuration handlers.
 *
 * Handles seeding, endpoint data exposure, and per-request config application
 * for specific configuration domains (core, database, cache, etc.).
 */
abstract class TenantConfigHandler
{
    /**
     * Get default configuration data for tenant seeding.
     *
     * @return array Configuration data to merge into tenant config during seeding
     */
    abstract public function getSeedData(): array;

    /**
     * Get configuration data that should be exposed via tenant API endpoints.
     *
     * @return array Configuration data safe for public/protected endpoint exposure
     */
    public function getEndpointData(): array
    {
        return [];
    }

    /**
     * Apply tenant configuration to the current HTTP request.
     *
     * This method runs during request processing to override Laravel's
     * configuration with tenant-specific values.
     *
     * @param Tenant $tenant The current tenant
     * @param ConfigRepository $config Laravel's config repository
     * @param array $tenantConfig The tenant's configuration array
     */
    public function applyToRequest(Tenant $tenant, ConfigRepository $config, array $tenantConfig): void
    {
        //
    }
}