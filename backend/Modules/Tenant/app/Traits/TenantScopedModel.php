<?php

namespace Modules\Tenant\app\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\app\Exceptions\TenantMismatchException;
use Modules\Tenant\app\Scopes\TenantScope;

trait TenantScopedModel
{
    use GetsTenant;

    /**
     * Boot the trait and apply the TenantScope automatically.
     */
    protected static function bootTenantScopedModel(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(
            fn (Model $model): mixed => $model->tenant_id ??= $model->getTenant()->id
        );
    }

    /**
     * Ensure `save()` enforces `tenant_id` and blocks cross-tenant saves.
     */
    public function save(array $options = []): bool
    {
        $this->guardWithTenantId();

        return parent::save($options);
    }

    /**
     * Ensure `delete()` enforces `tenant_id` and blocks cross-tenant deletions.
     */
    public function delete(): bool
    {
        $this->guardWithTenantId();

        return parent::where('id', '=', $this->id)
            ->delete();
    }

    /**
     * Override `update()` to enforce `tenant_id`.
     * Global scope automatically applies `tenant_id`, so no need to add it manually.
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        $this->guardWithTenantId();

        return parent::where('id', '=', $this->id)
            ->update($attributes, $options);
    }

    private function guardWithTenantId(): void
    {
        $tenantId = static::getTenant()->id;

        if (empty($this->tenant_id)) {
            $this->tenant_id = $tenantId;
        }

        if ($this->tenant_id !== $tenantId) {
            throw new TenantMismatchException();
        }
    }
}
