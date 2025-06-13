<?php

namespace Modules\Tenant\Tables;

use Modules\Tenant\Contracts\TenantTableConfigInterface;
use Modules\Tenant\ValueObjects\TenantTableConfig;

/**
 * Table configuration for users table.
 */
class UsersTableConfig implements TenantTableConfigInterface
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
            dropUniques: [
                ['email'],
                ['provider_id'],
            ],
            tenantUniqueConstraints: [
                ['email'],
                ['provider_id'],
                ['email', 'provider_id'],
            ]
        );
    }
}