<?php

namespace Modules\Auth\Tests\Unit\Actions\Socialite;

use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Mockery;
use Modules\Auth\Actions\Socialite\CreateClientNonceAction;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Services\AuthCoordinator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use Throwable;

#[CoversClass(CreateClientNonceAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class CreateClientNonceActionTest extends TestCase
{
    private Mockery\MockInterface|AuthCoordinator $authCoordinator;

    private Mockery\MockInterface|ResponseFactory $responseFactory;

    private CreateClientNonceAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->authCoordinator = Mockery::mock(AuthCoordinator::class);
        $this->responseFactory = Mockery::mock(ResponseFactory::class);

        // Instantiate the action with mocked dependencies
        $this->action = new CreateClientNonceAction(
            authCoordinator: $this->authCoordinator,
            responseFactory: $this->responseFactory,
        );
    }

    /**
     * @throws Throwable
     * @throws OAuthException
     */
    public function test_creates_client_nonce(): void
    {
        // Arrange
        $expectedNonce = 'test-nonce-123';
        $expectedPayload = ['nonce' => $expectedNonce];
        $jsonResponse = new JsonResponse($expectedPayload, 200);

        // The AuthCoordinator should return the nonce
        $this->authCoordinator
            ->shouldReceive('createClientNonce')
            ->once()
            ->andReturn($expectedNonce);

        // The ResponseFactory should turn that payload into a JsonResponse
        $this->responseFactory
            ->shouldReceive('json')
            ->once()
            ->with($expectedPayload)
            ->andReturn($jsonResponse);

        // Act
        $response = $this->action->__invoke();

        // Assert
        $this->assertSame($jsonResponse, $response);

        $responseData = $response->getData(true);
        $this->assertArrayHasKey('nonce', $responseData);
        $this->assertEquals($expectedNonce, $responseData['nonce']);
    }

    /**
     * @throws Throwable
     */
    public function test_converts_throwable_to_oauth_exception(): void
    {
        // Arrange
        $generalException = new Exception('General error');
        $this->authCoordinator->shouldReceive('createClientNonce')
            ->once()
            ->andThrow($generalException);

        // Assert
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INTERNAL_ERROR->value);

        // Act
        $this->action->__invoke();
    }
}
