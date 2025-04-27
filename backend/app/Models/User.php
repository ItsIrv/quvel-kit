<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Modules\Tenant\Models\Tenant;
use Modules\Tenant\Traits\TenantScopedModel;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $public_id
 * @property string $name
 * @property string $email
 * @property string $email_verified_at
 * @property string $password
 * @property string $remember_token
 * @property string $created_at
 * @property string $updated_at
 * @property string $provider_id
 * @property string $avatar
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Modules\Tenant\Database\Factories\TenantFactory> */
    use HasFactory;

    use Notifiable;
    use TenantScopedModel;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'name',
        'email',
        'password',
        'provider_id',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    /**
     * @return BelongsTo<Tenant, User>
     */
    public function tenant(): BelongsTo
    {
        /**
         * @var BelongsTo<Tenant, User>
         */
        return $this->belongsTo(Tenant::class);
    }
}
