<?php

namespace Modules\Tenant\Services;

use Modules\Tenant\ValueObjects\TenantTableConfig;

class TenantTableRegistry
{
    /**
     * Registered tenant-aware tables.
     * @var array<string, mixed>
     */
    protected array $tables = [];

    /**
     * Whether tables have been loaded from modules.
     */
    private bool $loaded = false;

    public function __construct(
        private TenantModuleConfigLoader $loader
    ) {
    }

    /**
     * Ensure tables are loaded from modules.
     */
    private function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }

        // Load tables from all modules
        foreach ($this->loader->getAllTables() as $tableName => $tableConfig) {
            $this->registerTable($tableName, $tableConfig);
        }

        $this->loaded = true;
    }

    /**
     * Register a table to be made tenant-aware.
     *
     * @param string $tableName The name of the table
     * @param array<string, mixed>|TenantTableConfig|string $config Configuration for the table
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
        } elseif (is_array($config)) {
            $this->tables[$tableName] = TenantTableConfig::fromArray($config);
        }
    }

    /**
     * Register multiple tables at once.
     *
     * @param array<string, mixed> $tables Array of table configurations
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
     * @return array<string, mixed>
     */
    public function getTables(): array
    {
        $this->ensureLoaded();
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
        $this->ensureLoaded();
        return $this->tables[$tableName] ?? null;
    }

    /**
     * Get configuration for a specific table as array (backward compatibility).
     *
     * @param string $tableName
     * @return array<string, mixed>|null
     */
    public function getTableConfigArray(string $tableName): ?array
    {
        $this->ensureLoaded();
        $config = $this->getTableConfig($tableName);
        return $config !== null ? $config->toArray() : null;
    }

    /**
     * Check if a table is registered.
     *
     * @param string $tableName
     * @return bool
     */
    public function hasTable(string $tableName): bool
    {
        $this->ensureLoaded();
        return isset($this->tables[$tableName]);
    }
}
