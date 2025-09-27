<?php

declare(strict_types=1);

namespace Quvel\Tenant\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Tenant model - simplified and clean.
 *
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $identifier
 * @property int|null $parent_id
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 * @property array $config
 */
class Tenant extends Model
{
    use SoftDeletes;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('tenant.table_name', 'tenants'));
    }

    protected $fillable = [
        'public_id',
        'name',
        'identifier',
        'parent_id',
        'is_active',
        'config',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'config' => 'array',
    ];

    /**
     * Parent tenant relationship.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Child tenants relationship.
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Get a config value by key.
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Set a config value by key.
     */
    public function setConfig(string $key, mixed $value): self
    {
        $config = $this->config ?? [];

        data_set($config, $key, $value);

        $this->config = $config;

        return $this;
    }

    /**
     * Check if config key exists.
     */
    public function hasConfig(string $key): bool
    {
        return data_get($this->config, $key) !== null;
    }

    /**
     * Remove a config key.
     */
    public function forgetConfig(string $key): self
    {
        $config = $this->config ?? [];

        data_forget($config, $key);

        $this->config = $config;

        return $this;
    }

    /**
     * Merge config values.
     */
    public function mergeConfig(array $values): self
    {
        $this->config = array_merge($this->config ?? [], $values);

        return $this;
    }
}