<?php

namespace Modules\Tenant\Services;

use Modules\Tenant\ValueObjects\TenantTableConfig;

class TenantTableRegistry
{
    /**
     * Registered tenant-aware tables.
     */
    protected array $tables = [];

    /**
     * Register a table to be made tenant-aware.
     *
     * @param string $tableName The name of the table
     * @param array|TenantTableConfig|string $config Configuration for the table
     * @return void
     */
    public function registerTable(string $tableName, array|TenantTableConfig|string $config = []): void
    {
        if ($config instanceof TenantTableConfig) {
            $this->tables[$tableName] = $config;
        } elseif (is_string($config) && class_exists($config)) {
            // Handle class references
            $instance = app($config);
            if ($instance instanceof \Modules\Tenant\Contracts\TenantTableConfigInterface) {
                $this->tables[$tableName] = $instance->getConfig();
            }
        } else {
            $this->tables[$tableName] = TenantTableConfig::fromArray($config);
        }
    }

    /**
     * Register multiple tables at once.
     *
     * @param array $tables Array of table configurations
     * @return void
     */
    public function registerTables(array $tables): void
    {
        foreach ($tables as $tableName => $config) {
            $this->registerTable($tableName, $config);
        }
    }

    /**
     * Get all registered tables.
     *
     * @return array
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * Get configuration for a specific table.
     *
     * @param string $tableName
     * @return TenantTableConfig|null
     */
    public function getTableConfig(string $tableName): ?TenantTableConfig
    {
        return $this->tables[$tableName] ?? null;
    }

    /**
     * Get configuration for a specific table as array (backward compatibility).
     *
     * @param string $tableName
     * @return array|null
     */
    public function getTableConfigArray(string $tableName): ?array
    {
        $config = $this->getTableConfig($tableName);
        return $config ? $config->toArray() : null;
    }

    /**
     * Check if a table is registered.
     *
     * @param string $tableName
     * @return bool
     */
    public function hasTable(string $tableName): bool
    {
        return isset($this->tables[$tableName]);
    }
}
