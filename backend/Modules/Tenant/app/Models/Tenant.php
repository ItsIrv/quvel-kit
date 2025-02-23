<?php

namespace Modules\Tenant\app\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Tenant\Database\Factories\TenantFactory;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'domain'];

    /**
     * Define relationship with TenantDomain.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(TenantDomain::class);
    }

    /**
     * Resolve a tenant by domain.
     */
    public static function resolveTenantByDomain(string $domain): ?Tenant
    {
        return self::where('domain', $domain)
            // ->orWhereHas('domains', fn ($query) => $query->where('domain', $domain)) // TODO: Fallback domains
            ->first();
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }
}
