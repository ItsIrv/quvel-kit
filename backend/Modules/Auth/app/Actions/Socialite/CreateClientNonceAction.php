<?php

namespace Modules\Auth\Actions\Socialite;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Services\OAuthCoordinator;
use Modules\Auth\Logs\Actions\Socialite\CreateClientNonceActionLogs;
use Throwable;

/**
 * Creates a new client nonce to begin the stateless socialite flow.
 */
class CreateClientNonceAction
{
    public function __construct(
        private readonly OAuthCoordinator $authCoordinator,
        private readonly ResponseFactory $responseFactory,
        private readonly CreateClientNonceActionLogs $logs,
    ) {
    }

    /**
     * @throws OAuthException|Throwable
     */
    public function __invoke(): JsonResponse
    {
        $request = request();
        
        try {
            $nonce = $this->authCoordinator->createClientNonce();
            
            $this->logs->nonceCreated(
                $nonce,
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
            
            return $this->responseFactory->json([
                'nonce' => $nonce,
            ]);
        } catch (Throwable $e) {
            $this->logs->nonceCreationFailed(
                $e->getMessage(),
                $request->ip() ?? 'unknown',
                $request->userAgent(),
            );
            
            if (!$e instanceof OAuthException) {
                $e = new OAuthException(OAuthStatusEnum::INTERNAL_ERROR, $e);
            }

            throw $e;
        }
    }
}
