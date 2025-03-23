<?php

namespace App\Services\User;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service to handle user lookup operations.
 */
class UserFindService
{
    /**
     * Find a user by ID.
     *
     * @throws ModelNotFoundException
     */
    public function findById(int|string $id): User
    {
        /** @phpstan-ignore-next-line TODO: */
        return User::where('id', '=', $id)->firstOrFail();
    }

    /**
     * Find a user by email.
     */
    public function findByEmail(string $email): ?User
    {
        /** @phpstan-ignore-next-line TODO: */
        return User::where('email', '=', $email)->first();
    }

    /**
     * Find a user by a field and value.
     */
    public function findByField(string $field, string $value): ?User
    {
        /** @phpstan-ignore-next-line TODO: */
        return User::where($field, '=', $value)->first();
    }
}
