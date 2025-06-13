<?php

namespace Modules\Tenant\ValueObjects;

/**
 * Immutable value object representing tenant table configuration.
 * 
 * Contains all the settings needed to make a database table tenant-aware.
 */
readonly class TenantTableConfig
{
    public function __construct(
        /**
         * Column after which the tenant_id should be added.
         */
        public string $after = 'id',

        /**
         * Whether tenant deletion should cascade to this table.
         */
        public bool $cascadeDelete = true,

        /**
         * List of unique constraints to drop before adding tenant-specific ones.
         * Each entry is an array of columns that form a unique constraint.
         */
        public array $dropUniques = [],

        /**
         * Unique constraints that should include tenant_id.
         * Each entry is an array of columns that should be unique together within a tenant.
         */
        public array $tenantUniqueConstraints = []
    ) {}

    /**
     * Convert to array format for backward compatibility.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'after' => $this->after,
            'cascade_delete' => $this->cascadeDelete,
            'drop_uniques' => $this->dropUniques,
            'tenant_unique_constraints' => $this->tenantUniqueConstraints,
        ];
    }

    /**
     * Create from array format for backward compatibility.
     *
     * @param array $config
     * @return static
     */
    public static function fromArray(array $config): static
    {
        return new static(
            after: $config['after'] ?? 'id',
            cascadeDelete: $config['cascade_delete'] ?? true,
            dropUniques: $config['drop_uniques'] ?? [],
            tenantUniqueConstraints: $config['tenant_unique_constraints'] ?? []
        );
    }
}