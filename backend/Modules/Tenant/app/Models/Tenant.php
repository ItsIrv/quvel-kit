<?php

namespace Modules\Tenant\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Tenant\Casts\DynamicTenantConfigCast;
use Modules\Tenant\Database\Factories\TenantFactory;
use Modules\Tenant\ValueObjects\DynamicTenantConfig;
use Modules\Tenant\ValueObjects\TenantConfig;

/**
 * Class Tenant
 *
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $domain
 * @property int|null $parent_id
 * @property DynamicTenantConfig|TenantConfig $config
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
        'config' => DynamicTenantConfigCast::class,
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
     * @return DynamicTenantConfig|TenantConfig|null
     */
    public function getEffectiveConfig(): DynamicTenantConfig|TenantConfig|null
    {
        $config = $this->config;
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

        // If both exist and at least one is DynamicTenantConfig, merge them
        if ($config instanceof DynamicTenantConfig || $parentConfig instanceof DynamicTenantConfig) {
            // Convert to DynamicTenantConfig if needed
            $childDynamic = $config instanceof DynamicTenantConfig 
                ? $config 
                : new DynamicTenantConfig($config->toArray());
                
            $parentDynamic = $parentConfig instanceof DynamicTenantConfig 
                ? $parentConfig 
                : new DynamicTenantConfig($parentConfig->toArray());

            // Parent config is base, child config overrides
            $merged = clone $parentDynamic;
            $merged->merge($childDynamic);
            
            // Child tier takes precedence (if set in config)
            if ($childDynamic->getTier()) {
                $merged->setTier($childDynamic->getTier());
            }
            
            return $merged;
        }

        // Both are legacy TenantConfig, return child's config
        return $config;
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
