<?php

namespace Modules\Tenant\app\Traits;

use Modules\Tenant\app\Scopes\TenantScope;

trait TenantScopedModel
{
    /**
     * Boot the trait and apply the TenantScope automatically.
     */
    protected static function bootTenantScopedModel(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    /**
     * Ensure the model always has a tenant_id when creating.
     */
    protected static function booted(): void
    {
        static::creating(function ($model): void {
            if (!$model->tenant_id) {
                $model->tenant_id = null;
            }
        });
    }
}
