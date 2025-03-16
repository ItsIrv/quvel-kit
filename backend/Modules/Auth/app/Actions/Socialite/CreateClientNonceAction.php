<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\NonceSessionService;
use Psr\SimpleCache\InvalidArgumentException;
use Random\RandomException;

/**
 * Creates a new client nonce to begin the stateless socialite flow.
 */
class CreateClientNonceAction
{
    public function __construct(
        private readonly ClientNonceService $clientNonceService,
        private readonly NonceSessionService $nonceSessionService,
    ) {}

    /**
     * @throws InvalidArgumentException
     * @throws RandomException
     * @throws OAuthException
     */
    public function __invoke(): JsonResponse
    {
        $nonce = $this->clientNonceService->create();
        $this->nonceSessionService->setNonce($nonce);

        return response()->json([
            'nonce' => $nonce,
        ]);
    }
}
