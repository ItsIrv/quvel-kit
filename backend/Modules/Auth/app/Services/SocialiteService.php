<?php

namespace Modules\Auth\Services;

use Exception;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;

class SocialiteService
{
    /**
     * Get OAuth provider redirect URL.
     */
    public function getRedirectResponse(string $provider, bool $stateless, string $serverToken = ''): RedirectResponse
    {
        if ($stateless) {
            // @phpstan-ignore-next-line
            return Socialite::driver($provider)
                ->stateless()
                ->with(['state' => $serverToken])
                ->redirect();
        }

        // @phpstan-ignore-next-line
        return Socialite::driver($provider)
            ->redirect();
    }

    /**
     * Get user data from provider callback.
     */
    public function getProviderUser(string $provider, bool $stateless): SocialiteUser
    {
        $user = null;

        try {
            if ($stateless) {
                // @phpstan-ignore-next-line Laravel provides statelesss
                $user = Socialite::driver($provider)
                    ->stateless()
                    ->user();
            }

            $user = Socialite::driver($provider)
                ->user();

            if (!$user->getEmail() || !$user->getId()) {
                throw new OAuthException(OAuthStatusEnum::INVALID_USER);
            }

            return $user;
        } catch (Exception $e) {
            throw new OAuthException(OAuthStatusEnum::INVALID_USER);
        }
    }
}
