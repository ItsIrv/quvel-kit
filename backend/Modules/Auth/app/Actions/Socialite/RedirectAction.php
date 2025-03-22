<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Http\RedirectResponse;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedirectRequest;
use Modules\Auth\Services\OAuthCoordinator;
use Throwable;

/**
 * Redirects the user to the socialite provider.
 */
class RedirectAction
{
    public function __construct(
        private readonly OAuthCoordinator $authCoordinator,
    ) {}

    /**
     * Handle OAuth provider redirect.
     *
     * @throws OAuthException|Throwable
     */
    public function __invoke(RedirectRequest $request, string $provider): RedirectResponse
    {
        $nonce = $request->validated('nonce');

        try {
            return $this->authCoordinator->buildRedirectResponse($provider, $nonce);
        } catch (Throwable $e) {
            if (! $e instanceof OAuthException) {
                $e = new OAuthException(OAuthStatusEnum::INTERNAL_ERROR, $e);
            }

            throw $e;
        }
    }
}
