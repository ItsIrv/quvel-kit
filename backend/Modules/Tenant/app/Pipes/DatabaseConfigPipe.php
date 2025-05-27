<?php

namespace Modules\Tenant\Pipes;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\DatabaseManager;
use Modules\Tenant\Contracts\ConfigurationPipeInterface;
use Modules\Tenant\Logs\Pipes\DatabaseConfigPipeLogs;
use Modules\Tenant\Models\Tenant;

/**
 * Handles database configuration for tenants.
 * Octane-safe: Uses container for state storage instead of static properties.
 */
class DatabaseConfigPipe implements ConfigurationPipeInterface
{
    private const ORIGINAL_CONFIG_KEY = 'tenant.original_db_config';

    /**
     * Apply database configuration.
     */
    public function handle(Tenant $tenant, ConfigRepository $config, array $tenantConfig, callable $next): mixed
    {
        // Store original database config if not already stored (Octane-safe)
        if (!app()->has(self::ORIGINAL_CONFIG_KEY)) {
            app()->instance(self::ORIGINAL_CONFIG_KEY, [
                'default'     => $config->get('database.default'),
                'connections' => $config->get('database.connections'),
            ]);
        }

        // Check if tenant has database isolation feature (only if tiers are enabled)
        if (config('tenant.enable_tiers', false) && !$tenant->hasFeature('database_isolation')) {
            // Pass to next pipe without database changes for basic/standard tiers
            return $next([
                'tenant'       => $tenant,
                'config'       => $config,
                'tenantConfig' => $tenantConfig,
            ]);
        }

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
            if ($dbManager->getConnections() && array_key_exists($connection, $dbManager->getConnections())) {
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
     * Reset database connection.
     * Octane-safe: Uses container instance instead of static property.
     */
    public static function resetResources(): void
    {
        if (app()->has(self::ORIGINAL_CONFIG_KEY)) {
            try {
                $originalConfig = app(self::ORIGINAL_CONFIG_KEY);

                // Restore original configuration
                config([
                    'database.default'     => $originalConfig['default'],
                    'database.connections' => $originalConfig['connections'],
                ]);

                $dbManager = app(DatabaseManager::class);
                $dbManager->purge();
                $dbManager->setDefaultConnection($originalConfig['default']);
                $dbManager->reconnect($originalConfig['default']);

                if (app()->environment(['local', 'development', 'testing']) && app()->bound(DatabaseConfigPipeLogs::class)) {
                    app(DatabaseConfigPipeLogs::class)->connectionReset($originalConfig['default']);
                }

                // Clean up the stored config
                app()->forgetInstance(self::ORIGINAL_CONFIG_KEY);
            } catch (\Exception $e) {
                if (app()->bound(DatabaseConfigPipeLogs::class)) {
                    app(DatabaseConfigPipeLogs::class)->resetFailed($e->getMessage());
                }
            }
        }
    }

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

    public function priority(): int
    {
        return 90;
    }
}
