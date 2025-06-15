<?php

namespace Modules\Tenant\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Tenant\Exceptions\TenantMismatchException;
use Modules\Tenant\Models\Tenant;
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
        // Apply tenant scope globally
        static::addGlobalScope(new TenantScope());

        static::creating(
            /** @phpstan-ignore-next-line */
            static fn (Model $model): mixed => $model->tenant_id ??= $model->getTenant()->id,
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
     * @param  array<int|string, mixed>  $options
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
        $tenantId        = $this->getTenantId();
        $currentTenantId = $this->getAttribute('tenant_id');

        if ($currentTenantId !== null && $currentTenantId !== $tenantId) {
            throw new TenantMismatchException();
        } else {
            $this->setAttribute('tenant_id', $tenantId);
        }
    }

    /**
     * Get the channels the model should broadcast notifications on.
     * Includes tenant public_id and model public_id for better security and multi-tenancy support.
     */
    public function receivesBroadcastNotificationsOn(): string
    {
        $modelClass     = class_basename($this);
        $tenantPublicId = $this->tenant->public_id ?? $this->getTenantPublicId();
        /** @phpstan-ignore-next-line property.notFound */
        $modelPublicId  = $this->public_id ?? $this->getKey();

        return "tenant.{$tenantPublicId}.{$modelClass}.{$modelPublicId}";
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
