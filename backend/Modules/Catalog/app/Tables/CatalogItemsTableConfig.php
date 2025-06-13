<?php

namespace Modules\Catalog\Tables;

use Modules\Tenant\Contracts\TenantTableConfigInterface;
use Modules\Tenant\ValueObjects\TenantTableConfig;

/**
 * Table configuration for catalog_items table.
 */
class CatalogItemsTableConfig implements TenantTableConfigInterface
{
    /**
     * Get the table configuration.
     *
     * @return TenantTableConfig
     */
    public function getConfig(): TenantTableConfig
    {
        return new TenantTableConfig(
            after: 'id',
            cascadeDelete: true,
            dropUniques: [],
            tenantUniqueConstraints: []
        );
    }
}