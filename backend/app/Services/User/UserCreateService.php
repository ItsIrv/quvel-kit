<?php

namespace App\Services\User;

use App\Models\User;

/**
 * Service to create users.
 */
class UserCreateService
{
    public function create(array $data): User
    {
        return User::create($data);
    }
}
