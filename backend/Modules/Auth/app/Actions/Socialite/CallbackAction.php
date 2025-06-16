<?php

namespace Modules\Auth\Actions\Socialite;

use Modules\Core\Services\FrontendService;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Events\OAuthLoginResult;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\CallbackRequest;
use Modules\Auth\Services\OAuthCoordinator;
use Throwable;

/**
 * Handles the callback from the socialite provider.
 */
class CallbackAction
{
    public function __construct(
        private readonly OAuthCoordinator $authCoordinator,
        private readonly FrontendService $frontendService,
        private readonly EventDispatcher $eventDispatcher,
    ) {
    }

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
                (string) $request->validated('state', ''),
            );

            if ($result->isStateless()) {
                $this->eventDispatcher->dispatch(
                    new OAuthLoginResult(
                        $result->getSignedNonce() ?? '',
                        $result,
                    ),
                );

                $this->frontendService->setIsCapacitor(true);
            }

            return $this->frontendService->redirect(
                '',
                [
                    'message' => $result->getStatus()->value,
                ],
            );
        } catch (Throwable $e) {
            if (!$e instanceof OAuthException) {
                $e = new OAuthException(OAuthStatusEnum::INTERNAL_ERROR, $e);
            }

            throw $e;
        }
    }
}
