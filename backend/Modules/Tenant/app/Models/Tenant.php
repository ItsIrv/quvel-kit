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
     * Memoized effective configuration to avoid recomputation.
     */
    private ?DynamicTenantConfig $memoizedEffectiveConfig = null;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'domain', 'parent_id', 'config'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
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
     * @return BelongsTo<Tenant, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(__CLASS__, 'parent_id');
    }

    /**
     * Child tenants relationship.
     *
     * @return HasMany<Tenant, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(__CLASS__, 'parent_id');
    }

    /**
     * Get the effective tenant configuration.
     * Efficiently merges parent config with child config by working with raw arrays.
     *
     * @return DynamicTenantConfig|null
     */
    public function getEffectiveConfig(): DynamicTenantConfig|null
    {
        // Check if we have memoized result
        if ($this->memoizedEffectiveConfig !== null) {
            return $this->memoizedEffectiveConfig;
        }

        // Get config objects first
        $childConfig  = $this->config;
        $parentConfig = $this->parent?->config;

        // Fast path: no configs at all
        /** @phpstan-ignore-next-line booleanAnd.alwaysFalse,identical.alwaysFalse */
        if ($childConfig === null && $parentConfig === null) {
            return $this->memoizedEffectiveConfig = null;
        }

        // Fast path: only child config
        if ($parentConfig === null) {
            return $this->memoizedEffectiveConfig = $childConfig;
        }

        // Fast path: only parent config
        /** @phpstan-ignore-next-line identical.alwaysFalse */
        if ($childConfig === null) {
            return $this->memoizedEffectiveConfig = $parentConfig;
        }

        // Get raw config arrays for merging
        $childConfigArray  = $childConfig->toArray();
        $parentConfigArray = $parentConfig->toArray();

        // Merge configs efficiently using array operations
        $mergedData = array_merge(
            $parentConfigArray['config'] ?? [],
            $childConfigArray['config'] ?? []
        );

        $mergedVisibility = array_merge(
            $parentConfigArray['visibility'] ?? [],
            $childConfigArray['visibility'] ?? []
        );

        // Create merged config (no tier system)
        $mergedConfig = new DynamicTenantConfig($mergedData, $mergedVisibility);

        return $this->memoizedEffectiveConfig = $mergedConfig;
    }

    /**
     * Boot the model and set up event listeners to invalidate memoized config.
     */
    protected static function boot(): void
    {
        parent::boot();

        // Clear memoized config when config attribute is changed
        static::updating(function (Tenant $tenant) {
            if ($tenant->isDirty('config')) {
                $tenant->memoizedEffectiveConfig = null;
            }
        });
    }

    /**
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
