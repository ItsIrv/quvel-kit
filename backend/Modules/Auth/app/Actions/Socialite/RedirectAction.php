<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedirectRequest;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;
use Throwable;

/**
 * Redirects the user to the socialite provider.
 */
class RedirectAction
{
    public function __construct(
        private readonly SocialiteService $socialiteService,
        private readonly ServerTokenService $serverTokenService,
        private readonly ClientNonceService $clientNonceService,
    ) {}

    /**
     * Handle OAuth provider redirect.
     *
     * @throws OAuthException|Throwable
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
        } catch (Throwable $e) {
            if (! $e instanceof OAuthException) {
                $e = new OAuthException(OAuthStatusEnum::INTERNAL_ERROR, $e);
            }

            throw $e;
        }
    }
}
