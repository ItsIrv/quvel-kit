<?php

namespace Modules\Auth\Services;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;

class SocialiteService
{
    /**
     * Get OAuth provider redirect URL.
     */
    public function getRedirectResponse(string $provider, string $serverToken): RedirectResponse
    {
        // @phpstan-ignore-next-line Laravel provides statelesss
        return Socialite::driver($provider)
            ->stateless()
            ->with(['state' => $serverToken])
            ->redirect();
    }

    /**
     * Get user data from provider callback.
     */
    public function getProviderUser(string $provider): SocialiteUser
    {
        // @phpstan-ignore-next-line Laravel provides statelesss
        return Socialite::driver($provider)
            ->stateless()
            ->user();
    }
}
