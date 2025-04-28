<?php

namespace Modules\Auth\Actions\Fortify;

use App\Models\User;
use App\Services\User\UserCreateService;
use App\Services\User\UserFindService;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\RegisterActionException;
use Modules\Auth\Rules\NameRule;
use Modules\Auth\Rules\EmailRule;

/**
 * Fortify action to create a new user during registration.
 *
 * This action integrates with Laravel Fortify while maintaining
 * the existing business logic from the original RegisterAction.
 */
class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Create a new CreateNewUser instance.
     */
    public function __construct(
        private readonly UserFindService $userFindService,
        private readonly UserCreateService $userCreateService,
        private readonly Hasher $hasher,
    ) {
    }

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     * @throws RegisterActionException If the email is already in use
     */
    public function create(array $input): User
    {
        // Validate input data
        Validator::make($input, [
            'name'     => ['required', 'string', ...NameRule::RULES],
            'email'    => [
                'required',
                EmailRule::default(),
                // (personally don't like db calls in validation)
                // 'unique:users,email',
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        // Check if user already exists
        if ($this->userFindService->findByEmail($input['email'])) {
            throw new RegisterActionException(
                AuthStatusEnum::EMAIL_ALREADY_IN_USE,
            );
        }

        // Prepare user data with hashed password
        $userData = [
            'name'     => $input['name'],
            'email'    => $input['email'],
            'password' => $this->hasher->make($input['password']),
        ];

        // Create and return the user
        return $this->userCreateService->create($userData);
    }
}
