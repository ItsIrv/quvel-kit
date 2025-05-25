<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Models\Tenant;

/**
 * Handles database configuration for tenants.
 * Supports tiered isolation where some tenants may use shared databases.
 */
class DatabaseConfigPipe implements ConfigurationPipeInterface
{
    /**
     * Apply database configuration.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Check if tenant has database overrides
        $hasDbOverride = isset($tenantConfig['db_connection']) ||
            isset($tenantConfig['db_host']) ||
            isset($tenantConfig['db_database']);

        if ($hasDbOverride) {
            // Apply database configuration
            if (isset($tenantConfig['db_connection'])) {
                $config->set('database.default', $tenantConfig['db_connection']);
            }

            $connection = $tenantConfig['db_connection'] ?? 'mysql';

            if (isset($tenantConfig['db_host'])) {
                $config->set("database.connections.$connection.host", $tenantConfig['db_host']);
            }
            if (isset($tenantConfig['db_port'])) {
                $config->set("database.connections.$connection.port", $tenantConfig['db_port']);
            }
            if (isset($tenantConfig['db_database'])) {
                $config->set("database.connections.$connection.database", $tenantConfig['db_database']);
            }
            if (isset($tenantConfig['db_username'])) {
                $config->set("database.connections.$connection.username", $tenantConfig['db_username']);
            }
            if (isset($tenantConfig['db_password'])) {
                $config->set("database.connections.$connection.password", $tenantConfig['db_password']);
            }
        }
        // If no database override, tenant uses shared database with row-level isolation

        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    /**
     * Get the configuration keys this pipe handles.
     */
    public function handles(): array
    {
        return [
            'db_connection',
            'db_host',
            'db_port',
            'db_database',
            'db_username',
            'db_password',
        ];
    }

    /**
     * Get the priority for this pipe.
     */
    public function priority(): int
    {
        return 90; // Run after core config
    }
}
