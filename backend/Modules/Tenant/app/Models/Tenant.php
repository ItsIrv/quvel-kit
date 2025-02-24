<?php

namespace Modules\Tenant\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\database\factories\TenantFactory;

/**
 * Class Tenant
 *
 * @property int $id
 * @property string $public_id
 * @property string $name
 * @property string $domain
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 */
class Tenant extends Model
{
    /** @use HasFactory<\Modules\Tenant\database\factories\TenantFactory> */
    use HasFactory;

    protected $fillable = ['name', 'domain'];

    /**
     * Create a new factory instance for Tenant.
     * @return TenantFactory
     */
    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }
}
