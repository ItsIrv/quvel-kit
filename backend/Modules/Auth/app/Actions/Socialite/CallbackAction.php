<?php

namespace Modules\Auth\Actions\Socialite;

use App\Services\FrontendService;
use Exception;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\Factory as ViewFactory;
use Modules\Auth\app\Services\UserAuthenticationService;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Events\OAuthLoginSuccess;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\CallbackRequest;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;
use Psr\SimpleCache\InvalidArgumentException;

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
        private readonly EventDispatcher $eventDispatcher,
        private readonly ViewFactory $viewFactory,
    ) {}

    /**
     * Handle OAuth provider callback.
     * @throws InvalidArgumentException
     */
    public function __invoke(CallbackRequest $request, string $provider): RedirectResponse|Response
    {
        try {
            $signedToken = $request->validated('state', '');
            $clientNonce = $this->serverTokenService->getClientNonce(
                $signedToken,
            );
            $stateless = $clientNonce !== null;

            [$user, $status] = $this->authenticateUser(
                $provider,
                $stateless,
            );

            return match ($status) {
                OAuthStatusEnum::LOGIN_OK => $stateless
                ? $this->handleStatelessLogin(
                    $signedToken,
                    $clientNonce,
                    $user,
                )
                : $this->handleSessionLogin($user),
                default => $this->handleFailedLogin($status),
            };
        } catch (Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Authenticate the user via OAuth.
     * @throws OAuthException
     */
    private function authenticateUser(string $provider, bool $stateless): array
    {
        $providerUser = $this->socialiteService->getProviderUser(
            $provider,
            $stateless,
        );

        return $this->userAuthenticationService->handleOAuthLogin(
            $provider,
            $providerUser,
        );
    }

    /**
     * Handle a successful login in stateless mode.
     * @throws OAuthException
     */
    private function handleStatelessLogin(string $signedToken, string $clientNonce, $user): Response
    {
        $this->serverTokenService->forget($signedToken);
        $this->clientNonceService->assignUserToNonce($clientNonce, $user->id);
        $this->eventDispatcher->dispatch(new OAuthLoginSuccess(
            $this->clientNonceService->getSignedNonce($clientNonce),
        ));

        return response($this->viewFactory->make('auth::callback'));
    }

    /**
     * Handle a successful login in session-based mode.
     */
    private function handleSessionLogin($user): RedirectResponse
    {
        $this->userAuthenticationService->logInWithId($user->id);

        return $this->frontendService->redirectPage(
            '',
            ['message' => OAuthStatusEnum::LOGIN_OK->getTranslatedMessage()],
        );
    }

    /**
     * Handle a failed login attempt.
     */
    private function handleFailedLogin(OAuthStatusEnum $status): RedirectResponse
    {
        return $this->frontendService->redirectPage(
            '',
            ['message' => $status->getTranslatedMessage()],
        );
    }

    /**
     * Handle exceptions.
     */
    private function handleException(Exception $e): RedirectResponse
    {
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
