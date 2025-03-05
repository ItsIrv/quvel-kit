<?php

namespace Modules\Auth\Services;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Contracts\User as SocialiteUser;

class SocialiteService
{
    /**
     * Get OAuth provider redirect URL.
     */
    public function getRedirectResponse(string $provider, string $serverToken): RedirectResponse
    {
        return Socialite::driver($provider)
            ->stateless() // Required due to custom state handling
            ->with(['state' => $serverToken])
            ->redirect();
    }

    /**
     * Get user data from provider callback.
     */
    public function getProviderUser(string $provider): SocialiteUser
    {
        return Socialite::driver($provider)->stateless()->user();
    }
}
