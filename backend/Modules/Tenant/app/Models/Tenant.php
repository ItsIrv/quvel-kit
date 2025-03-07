<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\database\factories\TenantFactory;
use Modules\Tenant\Casts\TenantConfigCast;
use Modules\Tenant\ValueObjects\TenantConfig;

/**
 * Class Tenant
 *
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $domain
 * @property int|null $parent_id
 * @property TenantConfig|null $config
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property Tenant|null $parent
 *
 * @method static TenantFactory factory(...$parameters)
 */
class Tenant extends Model
{
    /** @use HasFactory<\Modules\Tenant\database\factories\TenantFactory> */
    use HasFactory;

    protected $fillable = ['name', 'domain', 'parent_id', 'config'];

    protected $casts = [
        'config' => TenantConfigCast::class,
    ];

    /**
     * Create a new factory instance for Tenant.
     * @return TenantFactory
     */
    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    /**
     * Parent tenant relationship.
     *
     * @return BelongsTo<Tenant, Tenant>
     */
    public function parent(): BelongsTo
    {
        /** @var BelongsTo<Tenant, Tenant> */
        return $this->belongsTo(Tenant::class, 'parent_id');
    }

    /**
     * Child tenants relationship.
     *
     * @return HasMany<Tenant, Tenant>
     */
    public function children(): HasMany
    {
        /** @var HasMany<Tenant, Tenant> */
        return $this->hasMany(Tenant::class, 'parent_id');
    }

    /**
     * Get the effective tenant configuration.
     * Always returns the **parent's config** if available.
     *
     * @return TenantConfig|null
     */
    public function getEffectiveConfig(): ?TenantConfig
    {
        return $this->parent?->config ?? $this->config;
    }
}
