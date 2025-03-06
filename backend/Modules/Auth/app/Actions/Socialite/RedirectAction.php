<?php

namespace Modules\Auth\Actions\Socialite;

use App\Services\FrontendService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedirectRequest;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;
use Exception;

class RedirectAction
{
    public function __construct(
        private readonly SocialiteService $socialiteService,
        private readonly ServerTokenService $serverTokenService,
        private readonly ClientNonceService $clientNonceService,
        private readonly FrontendService $frontendService,
    ) {
    }

    /**
     * Handle OAuth provider redirect.
     */
    public function __invoke(RedirectRequest $request, string $provider): RedirectResponse|JsonResponse
    {
        $stateless = $request->validated('stateless', false);

        try {
            if (!$stateless) {
                return $this->socialiteService->getRedirectResponse(
                    $provider,
                    false,
                );
            }

            // Validate client nonce
            $clientNonce = $this->clientNonceService->validateNonce(
                $request->validated('nonce'),
            );

            // Generate secure server token and associate it with client nonce
            $serverToken = $this->serverTokenService->generateServerToken(
                $clientNonce,
            );

            // Get OAuth redirect URL
            $redirectUrl = $this->socialiteService->getRedirectResponse(
                $provider,
                true,
                $serverToken,
            );

            return $redirectUrl;
        } catch (Exception $e) {
            return $this->frontendService->redirectPage(
                '',
                [
                    'message' => is_a($e, OAuthException::class)
                        ? $e->getTranslatedMessage() : $e->getMessage(),
                ],
            );
        }
    }
}
