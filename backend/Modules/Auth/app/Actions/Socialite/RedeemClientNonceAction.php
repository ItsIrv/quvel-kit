<?php

namespace Modules\Auth\Actions\Socialite;

use Modules\Core\Http\Resources\UserResource;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedeemNonceRequest;
use Modules\Auth\Services\OAuthCoordinator;
use Modules\Auth\Logs\Actions\Socialite\RedeemClientNonceActionLogs;
use Throwable;

/**
 * Redeems a client nonce and logs in the user.
 */
class RedeemClientNonceAction
{
    public function __construct(
        private readonly OAuthCoordinator $authCoordinator,
        private readonly ResponseFactory $responseFactory,
        private readonly RedeemClientNonceActionLogs $logs,
    ) {
    }

    /**
     * Redeems a client nonce and logs in the user.
     *
     * @throws OAuthException
     * @throws Throwable
     */
    public function __invoke(RedeemNonceRequest $request): JsonResponse
    {
        $nonce = (string) $request->validated('nonce', '');
        
        try {
            $user = $this->authCoordinator->redeemClientNonce($nonce);
            
            $this->logs->nonceRedeemed(
                $nonce,
                $user->id,
                $user->email,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
            
            return $this->responseFactory->json([
                'message' => OAuthStatusEnum::CLIENT_TOKEN_GRANTED->getTranslatedMessage(),
                'user'    => new UserResource($user),
            ]);
        } catch (OAuthException $e) {
            if ($e->getStatus() === OAuthStatusEnum::INVALID_NONCE) {
                $this->logs->invalidNonceRedemption(
                    $nonce,
                    $request->ip() ?? 'unknown',
                    $request->userAgent(),
                );
            } else {
                $this->logs->nonceRedemptionFailed(
                    $nonce,
                    $e->getMessage(),
                    $request->ip() ?? 'unknown',
                    $request->userAgent(),
                );
            }
            
            throw $e;
        } catch (Throwable $e) {
            $this->logs->nonceRedemptionFailed(
                $nonce,
                $e->getMessage(),
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
            
            $e = new OAuthException(OAuthStatusEnum::INTERNAL_ERROR, $e);
            throw $e;
        }
    }
}
