<?php

namespace Modules\Tenant\Contracts;

use Modules\Tenant\ValueObjects\TenantTableConfig;

/**
 * Contract for fluent tenant table configuration builders.
 *
 * Provides a clean, chainable API for configuring how tables
 * should be made tenant-aware.
 */
interface TableBuilderInterface
{
    /**
     * Specify the column after which tenant_id should be added.
     *
     * @param string $column Column name (typically 'id')
     * @return static
     */
    public function after(string $column): static;

    /**
     * Configure whether tenant deletion should cascade to this table.
     *
     * @param bool $cascade Whether to cascade delete (default: true)
     * @return static
     */
    public function cascadeDelete(bool $cascade = true): static;

    /**
     * Add a unique constraint to drop before making table tenant-aware.
     *
     * @param array $columns Column names that form the unique constraint
     * @return static
     */
    public function dropUnique(array $columns): static;

    /**
     * Add a tenant-scoped unique constraint.
     *
     * These constraints will be unique within a tenant but can
     * be duplicated across different tenants.
     *
     * @param array $columns Column names that should be unique within tenant
     * @return static
     */
    public function tenantUnique(array $columns): static;

    /**
     * Build and return the table configuration.
     *
     * @return TenantTableConfig
     */
    public function build(): TenantTableConfig;
}
