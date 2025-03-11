<?php

namespace Modules\Auth\Actions\Socialite;

use App\Services\FrontendService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
        private readonly SocialiteService $socialiteService,
        private readonly ServerTokenService $serverTokenService,
        private readonly ClientNonceService $clientNonceService,
        private readonly UserAuthenticationService $userAuthenticationService,
        private readonly FrontendService $frontendService,
    ) {
    }

    /**
     * Handle OAuth provider callback.
     */
    public function __invoke(CallbackRequest $request, string $provider): RedirectResponse|JsonResponse
    {
        try {
            $nonce       = $request->validated('state', '');
            $clientNonce = $this->serverTokenService->getClientNonce($nonce);
            $stateless   = $clientNonce !== null;

            // Retrieve provider user data (stateless determines method)
            $providerUser = $this->socialiteService->getProviderUser(
                $provider,
                $stateless,
            );

            // Authenticate user via OAuth
            [$user, $status] = $this->userAuthenticationService->handleOAuthLogin(
                $provider,
                $providerUser,
            );

            if ($status === OAuthStatusEnum::LOGIN_OK) {
                if ($stateless) {
                    // Stateless Flow: Assign nonce & return JSON response
                    $this->serverTokenService->forgetClientNonce($nonce);
                    $this->clientNonceService->assignUserToNonce($clientNonce, $user->id);

                    return response()->json([
                        'status'  => $status,
                        'message' => OAuthStatusEnum::CLIENT_TOKEN_GRANED->getTranslatedMessage(),
                    ]);
                }

                $this->userAuthenticationService->logInWithId($user->id);
            }

            return $this->frontendService->redirectPage(
                '',
                ['message' => $status->getTranslatedMessage()],
            );
        } catch (Exception $e) {
            return $this->frontendService->redirectPage(
                '',
                [
                    'message' => $e instanceof OAuthException
                        ? $e->getTranslatedMessage()
                        : $e->getMessage(),
                ],
            );
        }
    }
}
