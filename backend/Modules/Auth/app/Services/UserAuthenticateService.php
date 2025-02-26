<?php

namespace Modules\Auth\app\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

/**
 * Service to handle user authentication.
 */
class UserAuthenticateService
{
    /**
     * Attempt to authenticate a user with email and password.
     *
     * @param string $email
     * @param string $password
     * @return User
     * @throws BadRequestException
     */
    public function attempt(string $email, string $password): bool
    {
        return Auth::attempt([
            'email'    => $email,
            'password' => $password,
        ]);
    }

    public function loginUsingId(mixed $id): bool
    {
        return Auth::loginUsingId($id);
    }
}
