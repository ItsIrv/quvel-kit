<?php

namespace Modules\Auth\Tests\Unit\Actions\Socialite;

use App\Models\User;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Mockery;
use Modules\Auth\Actions\Socialite\RedeemClientNonceAction;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedeemNonceRequest;
use Modules\Auth\Services\OAuthCoordinator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use Throwable;

#[CoversClass(RedeemClientNonceAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class RedeemClientNonceActionTest extends TestCase
{
    private Mockery\MockInterface|OAuthCoordinator $authCoordinator;

    private Mockery\MockInterface|ResponseFactory $responseFactory;

    private RedeemClientNonceAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->authCoordinator = Mockery::mock(OAuthCoordinator::class);
        $this->responseFactory = Mockery::mock(ResponseFactory::class);

        // Instantiate the action with mocked dependencies
        $this->action = new RedeemClientNonceAction(
            authCoordinator: $this->authCoordinator,
            responseFactory: $this->responseFactory,
        );
    }

    /**
     * @throws Throwable
     * @throws OAuthException
     */
    public function test_redeem_client_nonce_successfully(): void
    {
        // Arrange
        $signedNonce = 'signed-nonce-123';
        $userId = 1;
        $userArray = ['id' => $userId, 'name' => 'Test User'];

        // We'll simulate a real Eloquent model or something convertible to array
        $mockUser = Mockery::mock(User::class);
        $mockUser->shouldReceive('jsonSerialize')->andReturn($userArray);

        // The request
        $request = Mockery::mock(RedeemNonceRequest::class);
        $request->shouldReceive('validated')->once()->with('nonce', '')->andReturn($signedNonce);

        // OAuthCoordinator returns the User
        $this->authCoordinator
            ->shouldReceive('redeemClientNonce')
            ->once()
            ->with($signedNonce)
            ->andReturn($mockUser);

        $expectedResponseData = [
            'user' => $mockUser,
            'message' => OAuthStatusEnum::LOGIN_OK->getTranslatedMessage(),
        ];
        $expectedJsonResponse = new JsonResponse($expectedResponseData);

        // The ResponseFactory should transform that array into a JsonResponse
        $this->responseFactory
            ->shouldReceive('json')
            ->once()
            ->with($expectedResponseData)
            ->andReturn($expectedJsonResponse);

        // Act
        $actualResponse = $this->action->__invoke($request);

        // Assert
        $this->assertEquals($expectedJsonResponse->getData(), $actualResponse->getData());
    }

    /**
     * @throws Throwable
     */
    public function test_redeem_client_nonce_oauth_exception_propagates(): void
    {
        // Arrange
        $signedNonce = 'signed-nonce-123';
        $oauthException = new OAuthException(OAuthStatusEnum::INVALID_NONCE);

        $request = Mockery::mock(RedeemNonceRequest::class);
        $request->shouldReceive('validated')->andReturn($signedNonce);

        // The coordinator throws an OAuthException
        $this->authCoordinator
            ->shouldReceive('redeemClientNonce')
            ->andThrow($oauthException);

        // Expect the same exception to bubble up
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INVALID_NONCE->value);

        // Act
        $this->action->__invoke($request);
    }

    /**
     * @throws Throwable
     */
    public function test_redeem_client_nonce_general_exception_is_wrapped(): void
    {
        // Arrange
        $signedNonce = 'signed-nonce-123';
        $genericException = new Exception('Some unexpected error');

        $request = Mockery::mock(RedeemNonceRequest::class);
        $request->shouldReceive('validated')->andReturn($signedNonce);

        // The coordinator throws a general exception
        $this->authCoordinator
            ->shouldReceive('redeemClientNonce')
            ->andThrow($genericException);

        // Expect the action to wrap it in an OAuthException(INTERNAL_ERROR)
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INTERNAL_ERROR->value);

        // Act
        $this->action->__invoke($request);
    }
}
