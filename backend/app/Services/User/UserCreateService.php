<?php

namespace App\Services\User;

use App\Models\User;

/**
 * Service to create users.
 */
class UserCreateService
{
    /**
     * Create a new user instance.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        /** @phpstan-ignore-next-line Laravel provides create */
        return User::create($data);
    }
}
