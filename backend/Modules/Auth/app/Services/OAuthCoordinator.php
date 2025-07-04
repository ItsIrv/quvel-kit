<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\DTO\OAuthCallbackResult;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Psr\SimpleCache\InvalidArgumentException;
use Random\RandomException;

// TODO: These big a** methods need to be their own strategies
class OAuthCoordinator
{
    public function __construct(
        private readonly SocialiteService $socialiteService,
        private readonly ServerTokenService $serverTokenService,
        private readonly ClientNonceService $clientNonceService,
        private readonly NonceSessionService $nonceSessionService,
        private readonly UserAuthenticationService $userAuthenticationService,
    ) {
    }

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
        if ($requestNonce !== null && $requestNonce !== '' && $requestNonce !== '0') {
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
    public function authenticateCallback(string $provider, string $signedToken): OAuthCallbackResult
    {
        $clientNonce  = $this->serverTokenService->getClientNonce($signedToken);
        $stateless    = $clientNonce !== null;
        $providerUser = $this->socialiteService->getProviderUser(
            $provider,
            $stateless,
        );

        [$user, $status] = $this->userAuthenticationService->handleOAuthLogin(
            $provider,
            $providerUser,
        );

        if ($status === OAuthStatusEnum::LOGIN_SUCCESS) {
            $stateless
                ? $this->completeStatelessLogin($signedToken, $clientNonce, $user->id)
                : $this->completeSessionLogin($user->id);
        }

        return new OAuthCallbackResult(
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
        $nonce  = $this->clientNonceService->getNonce($requestNonce);
        $userId = $this->clientNonceService->getUserIdFromNonce($nonce);
        $this->clientNonceService->forget($nonce);

        if ($userId === null) {
            throw new OAuthException(OAuthStatusEnum::INTERNAL_ERROR);
        }

        $user = $this->userAuthenticationService->logInWithId($userId);

        if (!$user instanceof User) {
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
