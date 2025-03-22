<?php

namespace Modules\Auth\Tests\Unit\Actions\Socialite;

use App\Services\FrontendService;
use Exception;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Mockery;
use Modules\Auth\Actions\Socialite\CallbackAction;
use Modules\Auth\DTO\OAuthAuthenticationResult;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Events\OAuthLoginSuccess;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\CallbackRequest;
use Modules\Auth\Services\OAuthCoordinator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use Throwable;

#[CoversClass(CallbackAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class CallbackActionTest extends TestCase
{
    private Mockery\MockInterface|OAuthCoordinator $authCoordinator;

    private Mockery\MockInterface|FrontendService $frontendService;

    private Mockery\MockInterface|EventDispatcher $eventDispatcher;

    private Mockery\MockInterface|ResponseFactory $responseFactory;

    private CallbackAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authCoordinator = Mockery::mock(OAuthCoordinator::class);
        $this->frontendService = Mockery::mock(FrontendService::class);
        $this->eventDispatcher = Mockery::mock(EventDispatcher::class);
        $this->responseFactory = Mockery::mock(ResponseFactory::class);

        $this->action = new CallbackAction(
            authCoordinator: $this->authCoordinator,
            frontendService: $this->frontendService,
            eventDispatcher: $this->eventDispatcher,
            responseFactory: $this->responseFactory,
        );
    }

    /**
     * @throws Throwable
     * @throws OAuthException
     */
    public function test_stateless_flow_returns_callback_view(): void
    {
        // Arrange
        $provider = 'google';
        $signedState = 'signed-state-123';

        $mockRequest = Mockery::mock(CallbackRequest::class);
        $mockRequest->shouldReceive('validated')
            ->with('state', '')
            ->once()
            ->andReturn($signedState);

        // Suppose the coordinator returns a stateless result
        $mockResult = Mockery::mock(OAuthAuthenticationResult::class);
        $mockResult->shouldReceive('isStateless')->once()->andReturn(true);
        $mockResult->shouldReceive('getSignedNonce')->once()->andReturn('signed-nonce-value');

        $this->authCoordinator
            ->shouldReceive('authenticateCallback')
            ->once()
            ->with($provider, $signedState)
            ->andReturn($mockResult);

        $this->eventDispatcher
            ->shouldReceive('dispatch')
            ->once()
            ->with(Mockery::type(OAuthLoginSuccess::class));

        $mockResponse = new Response('mock-callback-view', 200);
        $this->responseFactory
            ->shouldReceive('view')
            ->once()
            ->with('auth::callback')
            ->andReturn($mockResponse);

        // Act
        $response = $this->action->__invoke($mockRequest, $provider);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertStringContainsString('mock-callback-view', $response->getContent());
    }

    /**
     * @throws Throwable
     * @throws OAuthException
     */
    public function test_stateful_flow_redirects_with_status_message(): void
    {
        // Arrange
        $provider = 'google';
        $signedState = 'some-state'; // or empty
        $expectedStatus = OAuthStatusEnum::LOGIN_OK;

        $mockRequest = Mockery::mock(CallbackRequest::class);
        $mockRequest->shouldReceive('validated')
            ->with('state', '')
            ->once()
            ->andReturn($signedState);

        // Coordinator returns a stateful result
        $mockResult = Mockery::mock(OAuthAuthenticationResult::class);
        $mockResult->shouldReceive('isStateless')->once()->andReturn(false);
        $mockResult->shouldReceive('getStatus')->once()->andReturn($expectedStatus);

        $this->authCoordinator
            ->shouldReceive('authenticateCallback')
            ->once()
            ->with($provider, $signedState)
            ->andReturn($mockResult);

        $redirectResponse = new RedirectResponse('/somewhere');
        $this->frontendService
            ->shouldReceive('redirectPage')
            ->once()
            ->with('', ['message' => $expectedStatus->value])
            ->andReturn($redirectResponse);

        // Act
        $response = $this->action->__invoke($mockRequest, $provider);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/somewhere', $response->getTargetUrl());
    }

    /**
     * @throws Throwable
     */
    public function test_oauth_exception_is_propagated(): void
    {
        // Arrange
        $provider = 'google';
        $mockRequest = Mockery::mock(CallbackRequest::class);
        $mockRequest->shouldReceive('validated')->with('state', '')->andReturn('');

        $oauthEx = new OAuthException(OAuthStatusEnum::INVALID_NONCE);

        $this->authCoordinator
            ->shouldReceive('authenticateCallback')
            ->once()
            ->andThrow($oauthEx);

        // We expect the same exception to bubble up
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INVALID_NONCE->value);

        // Act
        $this->action->__invoke($mockRequest, $provider);
    }

    /**
     * @throws Throwable
     */
    public function test_general_exception_is_wrapped_as_oauth_exception(): void
    {
        // Arrange
        $provider = 'google';
        $mockRequest = Mockery::mock(CallbackRequest::class);
        $mockRequest->shouldReceive('validated')
            ->with('state')
            ->andThrow(new Exception('test'));

        // Expect the action to wrap it in an OAuthException(INTERNAL_ERROR)
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INTERNAL_ERROR->value);

        // Act
        $this->action->__invoke($mockRequest, $provider);
    }
}
