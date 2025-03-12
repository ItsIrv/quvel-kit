<?php

namespace Modules\Auth\Actions\Socialite;

use Exception;
use Illuminate\Http\JsonResponse;
use Modules\Auth\app\Services\UserAuthenticationService;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedeemNonceRequest;
use Modules\Auth\Services\ClientNonceService;

class RedeemClientNonceAction
{
    public function __construct(
        private readonly ClientNonceService $clientNonceService,
        private readonly UserAuthenticationService $userAuthenticationService,
    ) {
    }

    /**
     * Redeems a client nonce and logs in the user.
     */
    public function __invoke(RedeemNonceRequest $request): JsonResponse
    {
        try {
            $signedNonce = $request->validated('nonce');
            $nonce       = $this->clientNonceService->getNonce($signedNonce);
            $userId      = $this->clientNonceService->getUserIdFromNonce($nonce);

            if (!$userId) {
                throw new OAuthException(OAuthStatusEnum::INVALID_NONCE);
            }

            $user = $this->userAuthenticationService->logInWithId($userId);

            $this->clientNonceService->forget($nonce);

            return response()->json([
                'user'    => $user,
                'message' => OAuthStatusEnum::LOGIN_OK->getTranslatedMessage(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e instanceof OAuthException ? $e->getTranslatedMessage() : $e->getMessage(),
            ], 400);
        }
    }
}
