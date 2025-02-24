<?php

namespace Modules\Tenant\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Tenant\Database\Factories\TenantFactory;

class Tenant extends Model
{
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
