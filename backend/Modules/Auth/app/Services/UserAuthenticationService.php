<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Modules\Core\Services\User\UserCreateService;
use Modules\Core\Services\User\UserFindService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Modules\Auth\Enums\OAuthStatusEnum;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

/**
 * Service to handle user authentication.
 *
 * TODO: Move OAuth logic to a separate service and validate provider inputs.
 */
class UserAuthenticationService
{
    public function __construct(
        private readonly AuthFactory $auth,
        private readonly UserFindService $userFindService,
        private readonly UserCreateService $userCreateService,
    ) {
    }

    /**
     * Attempt to authenticate a user with email and password.
     *
     * @throws BadRequestException
     */
    public function attempt(string $email, string $password): bool
    {
        // @phpstan-ignore-next-line Laravel provides attempt
        return $this->auth->guard()->attempt([
            'email'    => $email,
            'password' => $password,
        ]);
    }

    public function logout(): void
    {
        // @phpstan-ignore-next-line Laravel provides logout
        $this->auth->guard()->logout();
    }

    /**
     * Handle user authentication via OAuth.
     *
     * @return array{0: User, 1: OAuthStatusEnum}
     */
    public function handleOAuthLogin(string $provider, SocialiteUser $providerUser): array
    {
        $providerIdentifier = "{$provider}_{$providerUser->getId()}"; // Full identifier (e.g., google_123456)

        // Find existing user by email
        $user = $this->userFindService->findByEmail(
            $providerUser->getEmail() ?? ''
        );

        if ($user) {
            // Ensure provider ID consistency (avoid hijacking)
            if ($user->provider_id !== $providerIdentifier) {
                return [$user, OAuthStatusEnum::EMAIL_TAKEN];
            }

            // Ensure email is verified
            if (!$user->hasVerifiedEmail() && config('auth.verify_email_before_login')) {
                return [$user, OAuthStatusEnum::EMAIL_NOT_VERIFIED];
            }

            // Login successful
            return [$user, OAuthStatusEnum::LOGIN_SUCCESS];
        }

        // If no user exists, create a new one
        $user = $this->userCreateService->create(
            [
                'email'       => $providerUser->getEmail(),
                'provider_id' => $providerIdentifier,
                'name'        => $providerUser->getName(),
                'avatar'      => $providerUser->getAvatar(),
                'password'    => null,
            ],
        );

        return [$user, OAuthStatusEnum::USER_CREATED];
    }

    /**
     * Log in a user using their ID.
     */
    public function logInWithId(int $id): User|bool
    {
        // @phpstan-ignore-next-line laravel provides loginUsingId
        return $this->auth->guard()->loginUsingId($id);
    }
}
