<?php

namespace Modules\Tenant\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Modules\Tenant\Casts\DynamicTenantConfigCast;
use Modules\Tenant\Database\Factories\TenantFactory;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;

/**
 * Class Tenant
 *
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $domain
 * @property int|null $parent_id
 * @property DynamicTenantConfig $config
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Tenant|null $parent
 *
 * @method static TenantFactory factory(...$parameters)
 * @method static Tenant find(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<Tenant> select(string $column)
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;
    use SoftDeletes;

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
        'config'    => DynamicTenantConfigCast::class,
        'is_active' => 'boolean',
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
     * Merges parent config with child config if both exist.
     *
     * @return DynamicTenantConfig|null
     */
    public function getEffectiveConfig(): DynamicTenantConfig|null
    {
        $config       = $this->config;
        $parentConfig = $this->parent?->config;

        if (!$config && !$parentConfig) {
            return null;
        }

        if (!$parentConfig) {
            return $config;
        }

        if (!$config) {
            return $parentConfig;
        }

        // Merge parent and child configs
        $childDynamic  = $config;
        $parentDynamic = $parentConfig;

        // Create a new DynamicTenantConfig with parent's data as a starting point
        $mergedConfig = new DynamicTenantConfig(
            [], // Empty data array to start
            [], // Empty visibility array to start
            $parentDynamic->getTier() // Start with parent tier
        );

        // Copy all parent values first
        foreach ($parentDynamic->toArray()['config'] as $key => $value) {
            $mergedConfig->set($key, $value);
        }

        // Copy parent visibility
        foreach ($parentDynamic->toArray()['visibility'] as $key => $visibility) {
            $mergedConfig->setVisibility($key, \Modules\Tenant\Enums\TenantConfigVisibility::from($visibility));
        }

        // Then override with child values
        foreach ($childDynamic->toArray()['config'] as $key => $value) {
            $mergedConfig->set($key, $value);
        }

        // Override with child visibility
        foreach ($childDynamic->toArray()['visibility'] as $key => $visibility) {
            $mergedConfig->setVisibility($key, \Modules\Tenant\Enums\TenantConfigVisibility::from($visibility));
        }

        // Child tier takes precedence if set
        if ($childDynamic->getTier() !== null) {
            $mergedConfig->setTier($childDynamic->getTier());
        }

        return $mergedConfig;
    }

    /**
     * @return HasMany<User, Tenant>
     */
    public function users(): HasMany
    {
        /** @var HasMany<User, Tenant> */
        return $this->hasMany(User::class);
    }
}
