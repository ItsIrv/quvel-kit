<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Services\OAuthCoordinator;
use Throwable;

/**
 * Creates a new client nonce to begin the stateless socialite flow.
 */
class CreateClientNonceAction
{
    public function __construct(
        private readonly OAuthCoordinator $authCoordinator,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    /**
     * @throws OAuthException|Throwable
     */
    public function __invoke(): JsonResponse
    {
        try {
            return $this->responseFactory->json([
                'nonce' => $this->authCoordinator->createClientNonce(),
            ]);
        } catch (Throwable $e) {
            if (!$e instanceof OAuthException) {
                $e = new OAuthException(OAuthStatusEnum::INTERNAL_ERROR, $e);
            }

            throw $e;
        }
    }
}
