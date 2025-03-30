<?php

namespace Modules\Tenant\Traits;

use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Exceptions\TenantMismatchException;
use Modules\Tenant\Scopes\TenantScope;

/**
 * Trait to be applied to Eloquent models to enforce `tenant_id`.
 *
 * @property int $tenant_id The tenant ID.
 */
trait TenantScopedModel
{
    use GetsTenant;

    /**
     * Boot the trait and apply the TenantScope automatically.
     */
    protected static function bootTenantScopedModel(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(
            /** @phpstan-ignore-next-line */
            static fn (Model $model): mixed => $model->tenant_id ??= $model->getTenant()->id
        );
    }

    /**
     * Ensure `save()` enforces `tenant_id` and blocks cross-tenant saves.
     *
     * @param  array<string, mixed>  $options
     *
     * @throws TenantMismatchException
     */
    public function save(array $options = []): bool
    {
        $this->guardWithTenantId();

        return parent::save($options);
    }

    /**
     * Ensure `delete()` enforces `tenant_id` and blocks cross-tenant deletions.
     *
     * @throws TenantMismatchException
     */
    public function delete(): bool
    {
        $this->guardWithTenantId();

        /** @phpstan-ignore-next-line */
        return parent::where('id', '=', $this->id)
            ->delete();
    }

    /**
     * Override `update()` to enforce `tenant_id`.
     * Global scope automatically applies `tenant_id`, so no need to add it manually.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $options
     *
     * @throws TenantMismatchException
     */
    public function update(array $attributes = [], array $options = []): bool
    {
        $this->guardWithTenantId();

        /** @phpstan-ignore-next-line */
        return parent::where('id', '=', $this->id)
            ->update($attributes, $options);
    }

    /**
     * @throws TenantMismatchException
     */
    private function guardWithTenantId(): void
    {
        $tenantId = static::getTenant()->id;

        if (empty($this->tenant_id)) {
            $this->tenant_id = $tenantId;
        }

        if ($this->tenant_id !== $tenantId) {
            throw new TenantMismatchException;
        }
    }
}
