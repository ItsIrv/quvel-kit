<?php

namespace Modules\Catalog\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Catalog\Database\Factories\CatalogItemFactory;
use Modules\Tenant\Traits\TenantScopedModel;

class CatalogItem extends Model
{
    /** @use HasFactory<CatalogItemFactory> */
    use HasFactory;

    use TenantScopedModel;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'name',
        'description',
        'is_public',
        'metadata',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): CatalogItemFactory
    {
        return CatalogItemFactory::new();
    }
}
