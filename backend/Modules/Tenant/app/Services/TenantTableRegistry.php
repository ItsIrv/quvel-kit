<?php

namespace Modules\Tenant\Services;

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
     * @param array $config Configuration for the table
     * @return void
     */
    public function registerTable(string $tableName, array $config = []): void
    {
        $defaultConfig = [
            'after'                     => 'id',
            'cascade_delete'            => true,
            'drop_uniques'              => [],
            'tenant_unique_constraints' => [],
        ];

        $this->tables[$tableName] = array_merge($defaultConfig, $config);
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
     * @return array|null
     */
    public function getTableConfig(string $tableName): ?array
    {
        return $this->tables[$tableName] ?? null;
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
