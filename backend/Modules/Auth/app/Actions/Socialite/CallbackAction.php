<?php

namespace Modules\Auth\Actions\Socialite;

use App\Services\FrontendService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\app\Services\UserAuthenticationService;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Events\OAuthLoginSuccess;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\CallbackRequest;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;

/**
 * Handles the callback from the socialite provider.
 */
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
    public function __invoke(CallbackRequest $request, string $provider): RedirectResponse|Response
    {
        try {
            $signedToken = $request->validated('state', '');
            $clientNonce = $this->serverTokenService->getClientNonce($signedToken);
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

            // Only on status LOGIN_OK shall we grant access; others are user-friendly failures
            if ($status === OAuthStatusEnum::LOGIN_OK) {
                if ($stateless) {
                    $this->serverTokenService->forget($signedToken);
                    $this->clientNonceService->assignUserToNonce($clientNonce, $user->id);

                    event(new OAuthLoginSuccess(
                        $this->clientNonceService->getSignedNonce($clientNonce),
                    ));

                    return response()->make("
                    <script>
                        setTimeout(() => window.close(), 500);
                    </script>
                ", 200, ['Content-Type' => 'text/html']);
                } else {
                    $this->userAuthenticationService->logInWithId($user->id);
                }
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
