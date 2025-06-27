<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Http\RedirectResponse;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedirectRequest;
use Modules\Auth\Services\OAuthCoordinator;
use Modules\Auth\Logs\Actions\Socialite\RedirectActionLogs;
use Throwable;

/**
 * Redirects the user to the socialite provider.
 */
class RedirectAction
{
    public function __construct(
        private readonly OAuthCoordinator $authCoordinator,
        private readonly RedirectActionLogs $logs,
    ) {
    }

    /**
     * Handle OAuth provider redirect.
     *
     * @throws OAuthException|Throwable
     */
    public function __invoke(RedirectRequest $request, string $provider): RedirectResponse
    {
        $nonce = $request->validated('nonce');
        if ($nonce !== null) {
            $nonce = (string) $nonce;
        }

        try {
            $response = $this->authCoordinator->buildRedirectResponse($provider, $nonce);
            
            $this->logs->redirectSuccess(
                $provider,
                $nonce,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
            
            return $response;
        } catch (OAuthException $e) {
            $this->logs->redirectFailed(
                $provider,
                $e->getMessage(),
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
            
            throw $e;
        } catch (Throwable $e) {
            $this->logs->redirectError(
                $provider,
                $e->getMessage(),
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
            
            $e = new OAuthException(OAuthStatusEnum::INTERNAL_ERROR, $e);
            throw $e;
        }
    }
}
