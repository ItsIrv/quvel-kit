<?php

namespace Modules\Auth\Actions\Socialite;

use App\Services\FrontendService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedirectRequest;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Redirects the user to the socialite provider.
 */
class RedirectAction
{
    public function __construct(
        private readonly SocialiteService $socialiteService,
        private readonly ServerTokenService $serverTokenService,
        private readonly ClientNonceService $clientNonceService,
        private readonly FrontendService $frontendService,
    ) {}

    /**
     * Handle OAuth provider redirect.
     *
     * @throws InvalidArgumentException
     */
    public function __invoke(RedirectRequest $request, string $provider): RedirectResponse|JsonResponse
    {
        $stateless = $request->has('nonce');

        try {
            if (! $stateless) {
                return $this->socialiteService->getRedirectResponse(
                    $provider,
                );
            }

            // Validate nonce
            $nonce = $this->clientNonceService->getNonce(
                $request->validated('nonce'),
                ClientNonceService::TOKEN_CREATED,
            );

            // Don't allow multiple redirects with the same token
            $this->clientNonceService->assignRedirectedToNonce($nonce);

            // Redirect with the custom state
            return $this->socialiteService->getRedirectResponse(
                $provider,
                $this->serverTokenService->create($nonce),
            );
        } catch (Exception $e) {
            return $this->frontendService->redirectPage(
                '',
                [
                    'message' => $e instanceof OAuthException
                        ? $e->getTranslatedMessage() : $e->getMessage(),
                ],
            );
        }
    }
}
