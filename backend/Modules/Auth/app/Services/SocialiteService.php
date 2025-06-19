<?php

namespace Modules\Auth\Services;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\SocialiteManager;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\GoogleProvider;
use Modules\Tenant\Contexts\TenantContext;

class SocialiteService
{
    public function __construct(
        private readonly SocialiteManager $socialiteManager,
        private readonly TenantContext $tenantContext,
    ) {
    }

    /**
     * Get OAuth provider redirect URL.
     */
    public function getRedirectResponse(
        string $provider,
        string $signedServerToken = '',
    ): RedirectResponse {
        $driver = $this->buildOAuthDriver($provider);

        return $signedServerToken !== ''
            ? $driver->stateless()->with(['state' => $signedServerToken])->redirect()
            : $driver->redirect();
    }

    /**
     * Get user data from provider callback.
     */
    public function getProviderUser(string $provider, bool $stateless): SocialiteUser
    {
        $driver = $this->buildOAuthDriver($provider);

        return $stateless ? $driver->stateless()->user() : $driver->user();
    }

    /**
     * Build the OAuth driver with a dynamic redirect URI.
     *
     * TODO: We should be able to remove this due to per-request config routing.
     */
    private function buildOAuthDriver(string $provider): AbstractProvider
    {
        return $this->socialiteManager->buildProvider(
            GoogleProvider::class,
            array_merge(
                $this->getProviderConfig($provider),
                [
                    'redirect' => $this->getRedirectUri($provider),
                ],
            ),
        );
    }

    /**
     * Generate the dynamic redirect URI for the provider.
     */
    private function getRedirectUri(string $provider): string
    {
        $appUrl = $this->tenantContext->getConfig()?->get('app_url');
        return "$appUrl/auth/provider/$provider/callback";
    }

    /**
     * Gets the base configuration for the provider.
     *
     * @return array<string, mixed>
     */
    private function getProviderConfig(string $provider): array
    {
        return config("services.$provider", []);
    }
}
