<?php

namespace Modules\Auth\Actions\Socialite;

use App\Services\FrontendService;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Events\OAuthLoginSuccess;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\CallbackRequest;
use Modules\Auth\Services\AuthCoordinator;
use Throwable;

/**
 * Handles the callback from the socialite provider.
 */
class CallbackAction
{
    public function __construct(
        private readonly AuthCoordinator $authCoordinator,
        private readonly FrontendService $frontendService,
        private readonly EventDispatcher $eventDispatcher,
        private readonly ResponseFactory $responseFactory,
    ) {}

    /**
     * Handle OAuth provider callback.
     *
     * @throws OAuthException|Throwable
     */
    public function __invoke(CallbackRequest $request, string $provider): RedirectResponse|Response
    {
        try {
            $result = $this->authCoordinator->authenticateCallback(
                $provider,
                $request->validated('state', '')
            );

            if ($result->isStateless()) {
                $this->eventDispatcher->dispatch(
                    new OAuthLoginSuccess($result->getSignedNonce() ?? '')
                );

                return $this->responseFactory->view('auth::callback');
            }

            return $this->frontendService->redirectPage(
                '',
                [
                    'message' => $result->getStatus()->value,
                ]
            );
        } catch (Throwable $e) {
            if (! $e instanceof OAuthException) {
                $e = new OAuthException(OAuthStatusEnum::INTERNAL_ERROR, $e);
            }

            throw $e;
        }
    }
}
