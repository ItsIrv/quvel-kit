<?php

namespace Modules\Auth\app\Services;

use App\Services\User\UserCreateService;
use App\Services\User\UserFindService;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

/**
 * Service to handle user authentication.
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
     * @param string $email
     * @param string $password
     * @return bool
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
     * @property string $provider
     * @property SocialiteUser $providerUser
     * @return array<int, mixed>
     */
    public function handleOAuthLogin(string $provider, SocialiteUser $providerUser): array
    {
        $providerIdentifier = "{$provider}_{$providerUser->getId()}"; // Full identifier (e.g., google_123456)

        // Find existing user by provider ID or email
        $user = $this->userFindService->findByField('provider_id', $providerIdentifier)
            ?? $this->userFindService->findByEmail($providerUser->getEmail() ?? '');

        if ($user) {
            // Ensure provider ID consistency (avoid hijacking)
            if (!$user->provider_id) {
                throw new OAuthException(
                    OAuthStatusEnum::EMAIL_TAKEN,
                );
            }

            // Ensure provider ID consistency (avoid hijacking)
            if ($user->provider_id !== $providerIdentifier) {
                throw new OAuthException(
                    OAuthStatusEnum::PROVIDER_ID_TAKEN,
                );
            }

            // Ensure email is verified
            if (!$user->email_verified_at) {
                throw new OAuthException(
                    OAuthStatusEnum::EMAIL_NOT_VERIFIED,
                );
            }

            // Login successful
            return [$user, OAuthStatusEnum::LOGIN_OK];
        }

        // If no user exists, create a new one
        $user = $this->userCreateService->create(
            [
                'email'       => $providerUser->getEmail(),
                'provider_id' => $providerIdentifier,
                'name'        => $providerUser->getName(),
                'avatar'      => $providerUser->getAvatar() ?? null,
                'password'    => null,
            ],
        );

        return [$user, OAuthStatusEnum::USER_CREATED];
    }
}
