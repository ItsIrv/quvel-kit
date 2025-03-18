<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedeemNonceRequest;
use Modules\Auth\Services\AuthCoordinator;
use Throwable;

/**
 * Redeems a client nonce and logs in the user.
 */
class RedeemClientNonceAction
{
    public function __construct(
        private readonly AuthCoordinator $authCoordinator,
        private readonly ResponseFactory $responseFactory,
    ) {}

    /**
     * Redeems a client nonce and logs in the user.
     *
     * @throws OAuthException
     * @throws Throwable
     */
    public function __invoke(RedeemNonceRequest $request): JsonResponse
    {
        try {
            return $this->responseFactory->json([
                'user' => $this->authCoordinator->redeemClientNonce(
                    $request->validated('nonce', '')
                ),
                'message' => OAuthStatusEnum::LOGIN_OK->getTranslatedMessage(),
            ]);
        } catch (Throwable $e) {
            if (! $e instanceof OAuthException) {
                $e = new OAuthException(OAuthStatusEnum::INTERNAL_ERROR, $e);
            }

            throw $e;
        }
    }
}
