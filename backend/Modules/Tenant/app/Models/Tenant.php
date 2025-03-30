<?php

namespace Modules\Tenant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Tenant\Casts\TenantConfigCast;
use Modules\Tenant\Database\Factories\TenantFactory;
use Modules\Tenant\ValueObjects\TenantConfig;

/**
 * Class Tenant
 *
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $domain
 * @property int|null $parent_id
 * @property TenantConfig $config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Tenant|null $parent
 *
 * @method static TenantFactory factory(...$parameters)
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['name', 'domain', 'parent_id', 'config'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, class-string>
     */
    protected $casts = [
        'config' => TenantConfigCast::class,
    ];

    /**
     * Create a new factory instance for Tenant.
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
        return $this->belongsTo(__CLASS__, 'parent_id');
    }

    /**
     * Child tenants relationship.
     *
     * @return HasMany<Tenant, Tenant>
     */
    public function children(): HasMany
    {
        /** @var HasMany<Tenant, Tenant> */
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    /**
     * Get the effective tenant configuration.
     * Always returns the parent's config if available.
     */
    public function getEffectiveConfig(): ?TenantConfig
    {
        return $this->parent->config ?? $this->config;
    }
}
