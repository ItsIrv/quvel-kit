<?php

namespace Modules\Auth\app\Services;

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

/**
 * Service to handle user authentication.
 */
class UserAuthenticationService
{
    /**
     * Attempt to authenticate a user with email and password.
     *
     * @param string $email
     * @param string $password
     * @return bool
     * @throws BadRequestException
     */
    public function attempt(string $email, string $password): bool
    {
        return Auth::attempt([
            'email'    => $email,
            'password' => $password,
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
    }
}
