<?php

namespace Modules\Auth\Services;

use Exception;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\SocialiteManager;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
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
        bool $stateless,
        string $serverToken = '',
    ): RedirectResponse {
        $driver = $this->buildOAuthDriver($provider);

        return $stateless
            ? $driver->stateless()->with(['state' => $serverToken])->redirect()
            : $driver->redirect();
    }

    /**
     * Get user data from provider callback.
     *
     * @throws OAuthException
     */
    public function getProviderUser(string $provider, bool $stateless): SocialiteUser
    {
        $driver = $this->buildOAuthDriver($provider);

        try {
            return $stateless ? $driver->stateless()->user() : $driver->user();
        } catch (Exception $e) {
            throw new OAuthException(OAuthStatusEnum::INVALID_USER);
        }
    }

    /**
     * Build the OAuth driver with a dynamic redirect URI.
     */
    private function buildOAuthDriver(string $provider): \Laravel\Socialite\Two\AbstractProvider
    {
        return $this->socialiteManager->buildProvider(
            \Laravel\Socialite\Two\GoogleProvider::class,
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
        return "{$this->tenantContext->getConfig()?->apiUrl}/auth/provider/{$provider}/callback";
    }

    /**
     * Gets the base configuration for the provider.
     * @return array<string, mixed>
     */
    private function getProviderConfig(string $provider): array
    {
        return config("services.{$provider}", []);
    }
}
