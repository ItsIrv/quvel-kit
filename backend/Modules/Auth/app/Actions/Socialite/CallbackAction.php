<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\UserAuthenticationService;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\CallbackRequest;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;

class CallbackAction
{
    public function __construct(
        protected readonly SocialiteService $socialiteService,
        protected readonly ServerTokenService $serverTokenService,
        protected readonly ClientNonceService $clientNonceService,
        protected readonly UserAuthenticationService $userAuthenticationService,
    ) {
    }

    /**
     * Handle OAuth provider callback.
     */
    public function __invoke(CallbackRequest $request, string $provider): JsonResponse
    {
        try {
            $state = $request->validated('state');
            // Validate the state (server token)
            $clientNonce = $this->serverTokenService->getClientNonce(
                $state,
            );

            if (!$clientNonce) {
                throw new OAuthException(
                    OAuthStatusEnum::INVALID_TOKEN,
                );
            }

            // Forget, one-time use.
            $this->serverTokenService->forgetClientNonce(
                $state,
            );

            // Retrieve provider user data
            $providerUser = $this->socialiteService->getProviderUser(
                $provider,
            );

            // Ensure the provider user data is valid
            if (!$providerUser->getEmail() || !$providerUser->getId()) {
                throw new OAuthException(
                    OAuthStatusEnum::INVALID_USER,
                );
            }

            // Create or get the user
            [$user, $status] = $this->userAuthenticationService->handleOAuthLogin(
                $provider,
                $providerUser,
            );

            // Assign nonce to user (client will exchange it for an auth session later)
            $this->clientNonceService->assignUserToNonce(
                $clientNonce,
                $user->id,
            );

            return response()->json([
                'status'  => $status,
                'message' => __(OAuthStatusEnum::CLIENT_TOKEN_GRANED->value),
            ]);
        } catch (OAuthException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
