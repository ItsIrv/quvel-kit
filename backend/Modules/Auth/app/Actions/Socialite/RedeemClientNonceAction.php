<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\UserAuthenticationService;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedeemNonceRequest;
use Modules\Auth\Services\ClientNonceService;
use Throwable;

/**
 * Redeems a client nonce and logs in the user.
 */
class RedeemClientNonceAction
{
    public function __construct(
        private readonly ClientNonceService $clientNonceService,
        private readonly UserAuthenticationService $userAuthenticationService,
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
            $signedNonce = $request->validated('nonce');
            $nonce = $this->clientNonceService->getNonce($signedNonce);
            $userId = $this->clientNonceService->getUserIdFromNonce($nonce);

            if (! $userId) {
                throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
            }

            $user = $this->userAuthenticationService->logInWithId($userId);

            $this->clientNonceService->forget($nonce);

            return $this->responseFactory->json([
                'user' => $user,
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
