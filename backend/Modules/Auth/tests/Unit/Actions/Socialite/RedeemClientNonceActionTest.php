<?php

namespace Modules\Auth\Tests\Unit\Actions\Socialite;

use App\Models\User;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Mockery;
use Modules\Auth\Actions\Socialite\RedeemClientNonceAction;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Http\Requests\RedeemNonceRequest;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\app\Services\UserAuthenticationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(RedeemClientNonceAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class RedeemClientNonceActionTest extends TestCase
{
    private Mockery\MockInterface|ClientNonceService $clientNonceService;
    private Mockery\MockInterface|UserAuthenticationService $userAuthenticationService;
    private Mockery\MockInterface|ResponseFactory $responseFactory;
    private RedeemClientNonceAction $action;

    public function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->clientNonceService        = Mockery::mock(ClientNonceService::class);
        $this->userAuthenticationService = Mockery::mock(UserAuthenticationService::class);
        $this->responseFactory           = Mockery::mock(ResponseFactory::class);

        // Instantiate the action with mocked dependencies
        $this->action = new RedeemClientNonceAction(
            $this->clientNonceService,
            $this->userAuthenticationService,
            $this->responseFactory,
        );
    }

    /**
     * Test successful nonce redemption and user login.
     */
    public function testRedeemClientNonceSuccessfully(): void
    {
        // Arrange
        $signedNonce = 'signed-nonce-123';
        $nonce       = 'nonce-456';
        $userId      = 1;
        $userArray   = ['id' => $userId, 'name' => 'Test User'];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('jsonSerialize')->once()->andReturn($userArray);

        // Ensure that jsonSerialize() is actually called before we pass it into responseFactory
        $serializedUser = $user->jsonSerialize();

        $expectedResponse = new JsonResponse([
            'user'    => $serializedUser,
            'message' => OAuthStatusEnum::LOGIN_OK->getTranslatedMessage(),
        ]);

        $request = Mockery::mock(RedeemNonceRequest::class);
        $request->shouldReceive('validated')->with('nonce')->once()->andReturn($signedNonce);

        $this->clientNonceService->shouldReceive('getNonce')
            ->with($signedNonce)
            ->once()
            ->andReturn($nonce);

        $this->clientNonceService->shouldReceive('getUserIdFromNonce')
            ->with($nonce)
            ->once()
            ->andReturn($userId);

        $this->userAuthenticationService->shouldReceive('logInWithId')
            ->with($userId)
            ->once()
            ->andReturn($user);

        $this->clientNonceService->shouldReceive('forget')
            ->with($nonce)
            ->once();

        $this->responseFactory->shouldReceive('json')
            ->with([
                'user'    => $user,
                'message' => OAuthStatusEnum::LOGIN_OK->getTranslatedMessage(),
            ])
            ->once()
            ->andReturn($expectedResponse);

        // Act
        $response = $this->action->__invoke($request);

        // Assert
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Test that an OAuthException is thrown when the nonce is invalid.
     */
    public function testRedeemClientNonceWithInvalidNonce(): void
    {
        // Arrange
        $signedNonce = 'invalid-nonce-123';
        $nonce       = 'nonce-456';

        $request = Mockery::mock(RedeemNonceRequest::class);
        $request->shouldReceive('validated')->with('nonce')->once()->andReturn($signedNonce);

        $this->clientNonceService->shouldReceive('getNonce')
            ->with($signedNonce)
            ->once()
            ->andReturn($nonce);

        // Simulate no user ID found for the nonce
        $this->clientNonceService->shouldReceive('getUserIdFromNonce')
            ->with($nonce)
            ->once()
            ->andReturn(null);

        $expectedResponse = new JsonResponse([
            'error' => OAuthStatusEnum::INVALID_NONCE->getTranslatedMessage(),
        ], 400);

        $this->responseFactory->shouldReceive('json')
            ->with(['error' => OAuthStatusEnum::INVALID_NONCE->getTranslatedMessage()], 400)
            ->once()
            ->andReturn($expectedResponse);

        // Act
        $response = $this->action->__invoke($request);

        // Assert
        $this->assertSame($expectedResponse, $response);
    }

    /**
     * Test that a general Exception is handled correctly.
     */
    public function testRedeemClientNonceHandlesGeneralException(): void
    {
        // Arrange
        $signedNonce = 'signed-nonce-123';

        $request = Mockery::mock(RedeemNonceRequest::class);
        $request->shouldReceive('validated')->with('nonce')->once()->andReturn($signedNonce);

        // Simulate an exception being thrown in getNonce()
        $this->clientNonceService->shouldReceive('getNonce')
            ->with($signedNonce)
            ->once()
            ->andThrow(new Exception('Unexpected error occurred'));

        $expectedResponse = new JsonResponse([
            'error' => 'Unexpected error occurred',
        ], 400);

        $this->responseFactory->shouldReceive('json')
            ->with(['error' => 'Unexpected error occurred'], 400)
            ->once()
            ->andReturn($expectedResponse);

        // Act
        $response = $this->action->__invoke($request);

        // Assert
        $this->assertSame($expectedResponse, $response);
    }
}
