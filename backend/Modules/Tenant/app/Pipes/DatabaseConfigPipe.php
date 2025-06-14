<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Modules\Tenant\Logs\Pipes\DatabaseConfigPipeLogs;
use Modules\Tenant\Models\Tenant;

/**
 * Handles database configuration for tenants.
 */
class DatabaseConfigPipe extends BaseConfigurationPipe
{
    /**
     * Apply database configuration to Laravel config repository.
     *
     * @param Tenant $tenant The tenant context
     * @param ConfigRepository $config Laravel config repository
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @param callable $next The next pipe in the pipeline
     * @return mixed Result of calling $next()
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Check if tenant has database overrides
        $hasDbOverride = isset($tenantConfig['db_connection']) ||
            isset($tenantConfig['db_host']) ||
            isset($tenantConfig['db_database']);

        if ($hasDbOverride) {
            $dbManager  = app(DatabaseManager::class);
            $connection = $tenantConfig['db_connection'] ?? 'mysql';

            // Apply database configuration
            if (isset($tenantConfig['db_connection'])) {
                $config->set('database.default', $tenantConfig['db_connection']);
            }

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

            // Purge the connection if it was previously established
            $connections = $dbManager->getConnections();
            if ($connections !== [] && array_key_exists($connection, $connections)) {
                $dbManager->purge($connection);
            }

            $dbManager->setDefaultConnection($connection);
            $dbManager->reconnect($connection);

            if (app()->environment(['local', 'development', 'testing']) && app()->bound(DatabaseConfigPipeLogs::class)) {
                app(DatabaseConfigPipeLogs::class)->connectionSwitched($connection, $tenant->name);
            }
        }

        return $next([
            'tenant'       => $tenant,
            'config'       => $config,
            'tenantConfig' => $tenantConfig,
        ]);
    }

    /**
     * Resolve database configuration for frontend TenantConfig interface.
     *
     * @param Tenant $tenant The tenant context
     * @param array<string, mixed> $tenantConfig The tenant configuration array
     * @return array<string, mixed> Empty array - database configuration is internal only
     */
    public function resolve(Tenant $tenant, array $tenantConfig): array
    {
        return [];
    }

}
