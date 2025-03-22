<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\DTO\OAuthAuthenticationResult;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Psr\SimpleCache\InvalidArgumentException;
use Random\RandomException;

class OAuthCoordinator
{
    public function __construct(
        private readonly SocialiteService $socialiteService,
        private readonly ServerTokenService $serverTokenService,
        private readonly ClientNonceService $clientNonceService,
        private readonly NonceSessionService $nonceSessionService,
        private readonly UserAuthenticationService $userAuthenticationService,
    ) {}

    /**
     * Create a client nonce and store it in the session.
     *
     * @throws InvalidArgumentException
     * @throws RandomException
     * @throws OAuthException
     */
    public function createClientNonce(): string
    {
        $nonce = $this->clientNonceService->create();
        $this->nonceSessionService->setNonce($nonce);

        return $nonce;
    }

    /**
     * Build a redirect response for OAuth.
     *
     * @throws InvalidArgumentException
     * @throws OAuthException
     * @throws RandomException
     */
    public function buildRedirectResponse(string $provider, ?string $requestNonce): RedirectResponse
    {
        $serverToken = '';
        if (! empty($requestNonce)) {
            $clientNonce = $this->clientNonceService->getNonce(
                $requestNonce,
                ClientNonceService::TOKEN_CREATED,
            );

            $this->clientNonceService->assignRedirectedToNonce($clientNonce);

            $serverToken = $this->serverTokenService->create($clientNonce);
        }

        return $this->socialiteService->getRedirectResponse($provider, $serverToken);
    }

    /**
     * Authenticate the callback from OAuth.
     *
     * @throws OAuthException
     * @throws InvalidArgumentException
     */
    public function authenticateCallback(string $provider, string $signedToken): OAuthAuthenticationResult
    {
        $clientNonce = $this->serverTokenService->getClientNonce($signedToken);
        $stateless = $clientNonce !== null;
        $providerUser = $this->socialiteService->getProviderUser(
            $provider,
            $stateless,
        );

        [$user, $status] = $this->userAuthenticationService->handleOAuthLogin(
            $provider,
            $providerUser,
        );

        if ($status === OAuthStatusEnum::LOGIN_OK) {
            $stateless
                ? $this->completeStatelessLogin($signedToken, $clientNonce, $user->id)
                : $this->completeSessionLogin($user->id);
        }

        return new OAuthAuthenticationResult(
            $user,
            $status,
            $stateless ? $this->clientNonceService->getSignedNonce($clientNonce) : null,
        );
    }

    /**
     * Redeem a client nonce for a session.
     *
     * @throws InvalidArgumentException
     * @throws OAuthException
     */
    public function redeemClientNonce(string $requestNonce): User
    {
        $nonce = $this->clientNonceService->getNonce($requestNonce);
        $this->clientNonceService->forget($nonce);

        $userId = $this->clientNonceService->getUserIdFromNonce($nonce);

        if (! $userId) {
            throw new OAuthException(OAuthStatusEnum::INTERNAL_ERROR);
        }

        $user = $this->userAuthenticationService->logInWithId($userId);

        if (! $user instanceof User) {
            throw new OAuthException(OAuthStatusEnum::INTERNAL_ERROR);
        }

        return $user;
    }

    /**
     * Complete stateless login.
     *
     * @throws OAuthException
     */
    private function completeStatelessLogin(string $signedToken, string $clientNonce, int $userId): void
    {
        $this->serverTokenService->forget($signedToken);
        $this->clientNonceService->assignUserToNonce($clientNonce, $userId);
    }

    /**
     * Handle a successful login in session-based mode.
     */
    private function completeSessionLogin(int $userId): void
    {
        $this->userAuthenticationService->logInWithId($userId);
    }
}
