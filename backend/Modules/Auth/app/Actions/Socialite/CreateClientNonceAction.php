<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\NonceSessionService;
use Throwable;

/**
 * Creates a new client nonce to begin the stateless socialite flow.
 */
class CreateClientNonceAction
{
    public function __construct(
        private readonly ClientNonceService $clientNonceService,
        private readonly NonceSessionService $nonceSessionService,
        private readonly ResponseFactory $responseFactory,
    ) {}

    /**
     * @throws OAuthException|Throwable
     */
    public function __invoke(): JsonResponse
    {
        try {
            $nonce = $this->clientNonceService->create();
            $this->nonceSessionService->setNonce($nonce);

            return $this->responseFactory->json([
                'nonce' => $nonce,
            ]);
        } catch (Throwable $e) {
            if (! $e instanceof OAuthException) {
                $e = new OAuthException(OAuthStatusEnum::INTERNAL_ERROR, $e);
            }

            throw $e;
        }
    }
}
