<?php

namespace Modules\Core\Services\User;

use App\Models\User;

/**
 * Service to handle user lookup operations.
 */
class UserFindService
{
    /**
     * Find a user by ID.
     */
    public function findById(int|string $id): User
    {
        return User::where('id', '=', $id)->firstOrFail();
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User
    {
        return User::where('email', '=', $email)->first();
    }

    /**
     * Find a user by public ID.
     */
    public function findByPublicId(string $publicId): ?User
    {
        return User::where('public_id', '=', $publicId)->first();
    }

    /**
     * Find a user by a field and value.
     */
    public function findByField(string $field, string $value): ?User
    {
        return User::where($field, '=', $value)->first();
    }
}
