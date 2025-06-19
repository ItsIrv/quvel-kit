<?php

namespace Modules\Tenant\Builders;

use Modules\Tenant\Contracts\TableBuilderInterface;
use Modules\Tenant\ValueObjects\TenantTableConfig;

/**
 * Fluent builder for tenant table configuration.
 *
 * Provides a clean, chainable API for configuring how tables
 * should be made tenant-aware.
 */
class TenantTableBuilder implements TableBuilderInterface
{
    private string $after = 'id';
    private bool $cascadeDelete = true;
    /** @var array<int, list<string>> */
    private array $dropUniques = [];
    /** @var array<int, list<string>> */
    private array $tenantUniqueConstraints = [];

    /**
     * Create a new table builder instance.
     *
     * @return static
     */
    public static function create(): static
    {
        /** @phpstan-ignore-next-line */
        return new static();
    }

    /**
     * Specify the column after which tenant_id should be added.
     *
     * @param string $column Column name (typically 'id')
     * @return static
     */
    public function after(string $column): static
    {
        $this->after = $column;
        return $this;
    }

    /**
     * Configure whether tenant deletion should cascade to this table.
     *
     * @param bool $cascade Whether to cascade delete (default: true)
     * @return static
     */
    public function cascadeDelete(bool $cascade = true): static
    {
        $this->cascadeDelete = $cascade;
        return $this;
    }

    /**
     * Add a unique constraint to drop before making table tenant-aware.
     *
     * @param list<string> $columns Column names that form the unique constraint
     * @return static
     */
    public function dropUnique(array $columns): static
    {
        $this->dropUniques[] = $columns;
        return $this;
    }

    /**
     * Add multiple unique constraints to drop.
     *
     * @param array<int, list<string>> $constraints Array of column arrays
     * @return static
     */
    public function dropUniques(array $constraints): static
    {
        foreach ($constraints as $constraint) {
            $this->dropUnique($constraint);
        }
        return $this;
    }

    /**
     * Add a tenant-scoped unique constraint.
     *
     * These constraints will be unique within a tenant but can
     * be duplicated across different tenants.
     *
     * @param list<string> $columns Column names that should be unique within tenant
     * @return static
     */
    public function tenantUnique(array $columns): static
    {
        $this->tenantUniqueConstraints[] = $columns;
        return $this;
    }

    /**
     * Add multiple tenant-scoped unique constraints.
     *
     * @param array<int, list<string>> $constraints Array of column arrays
     * @return static
     */
    public function tenantUniques(array $constraints): static
    {
        foreach ($constraints as $constraint) {
            $this->tenantUnique($constraint);
        }
        return $this;
    }

    /**
     * Build and return the table configuration.
     *
     * @return TenantTableConfig
     */
    public function build(): TenantTableConfig
    {
        return new TenantTableConfig(
            after: $this->after,
            cascadeDelete: $this->cascadeDelete,
            dropUniques: $this->dropUniques,
            tenantUniqueConstraints: $this->tenantUniqueConstraints
        );
    }
}
