<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\NonceSessionService;

class CreateClientNonceAction
{
    public function __construct(
        private readonly ClientNonceService $clientNonceService,
        private readonly NonceSessionService $nonceSessionService,
    ) {
    }

    public function __invoke(): JsonResponse
    {
        if ($this->nonceSessionService->isValid()) {
            throw new OAuthException(OAuthStatusEnum::ACTIVE_FLOW_EXISTS);
        }

        $nonce = $this->clientNonceService->create();
        $this->nonceSessionService->setNonce($nonce);

        return response()->json([
            'nonce' => $nonce,
        ]);
    }
}
