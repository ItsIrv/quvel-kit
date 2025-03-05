<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedirectRequest;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;

class RedirectAction
{
    public function __construct(
        protected readonly SocialiteService $socialiteService,
        protected readonly ServerTokenService $serverTokenService,
        protected readonly ClientNonceService $clientNonceService,
    ) {
    }

    /**
     * Handle OAuth provider redirect.
     */
    public function __invoke(RedirectRequest $request, string $provider): RedirectResponse|JsonResponse
    {
        try {
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
                $serverToken,
            );

            return $redirectUrl;
        } catch (OAuthException $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 400);
        }
    }
}
