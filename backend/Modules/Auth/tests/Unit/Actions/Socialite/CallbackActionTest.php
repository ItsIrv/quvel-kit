<?php

namespace Modules\Auth\Tests\Unit\Actions\Socialite;

use App\Models\User;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Mockery;
use Modules\Auth\Actions\Socialite\CallbackAction;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Events\OAuthLoginSuccess;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\CallbackRequest;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;
use Modules\Auth\app\Services\UserAuthenticationService;
use App\Services\FrontendService;
use Illuminate\View\Factory as ViewFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(CallbackAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class CallbackActionTest extends TestCase
{
    private Mockery\MockInterface|SocialiteService $socialiteService;
    private Mockery\MockInterface|ServerTokenService $serverTokenService;
    private Mockery\MockInterface|ClientNonceService $clientNonceService;
    private Mockery\MockInterface|UserAuthenticationService $userAuthenticationService;
    private Mockery\MockInterface|FrontendService $frontendService;
    private Mockery\MockInterface|EventDispatcher $eventDispatcher;
    private Mockery\MockInterface|ViewFactory $viewFactory;
    private CallbackAction $action;

    public function setUp(): void
    {
        parent::setUp();

        // Mock Dependencies
        $this->socialiteService          = Mockery::mock(SocialiteService::class);
        $this->serverTokenService        = Mockery::mock(ServerTokenService::class);
        $this->clientNonceService        = Mockery::mock(ClientNonceService::class);
        $this->userAuthenticationService = Mockery::mock(UserAuthenticationService::class);
        $this->frontendService           = Mockery::mock(FrontendService::class);
        $this->eventDispatcher           = Mockery::mock(EventDispatcher::class);
        $this->viewFactory               = Mockery::mock(ViewFactory::class);

        // Instantiate the action with mocks
        $this->action = new CallbackAction(
            $this->socialiteService,
            $this->serverTokenService,
            $this->clientNonceService,
            $this->userAuthenticationService,
            $this->frontendService,
            $this->eventDispatcher,
            $this->viewFactory,
        );
    }

    /**
     * Test successful OAuth callback with stateless login.
     */
    public function testSuccessfulOAuthCallbackStateless(): void
    {
        // Arrange
        $provider      = 'google';
        $signedToken   = 'signed-token-123';
        $clientNonce   = 'nonce-456';
        $user          = User::first();
        $socialiteUser = Mockery::mock(SocialiteUser::class);

        $request = Mockery::mock(CallbackRequest::class);
        $request->shouldReceive('validated')
            ->with('state', '')
            ->once()
            ->andReturn($signedToken);

        $this->serverTokenService->shouldReceive('getClientNonce')
            ->with($signedToken)
            ->once()
            ->andReturn($clientNonce);

        $this->socialiteService->shouldReceive('getProviderUser')
            ->with($provider, true)
            ->once()
            ->andReturn($socialiteUser);

        $this->userAuthenticationService->shouldReceive('handleOAuthLogin')
            ->with($provider, $socialiteUser)
            ->once()
            ->andReturn([$user, OAuthStatusEnum::LOGIN_OK]);

        $this->serverTokenService->shouldReceive('forget')
            ->with($signedToken)
            ->once();

        $this->clientNonceService->shouldReceive('assignUserToNonce')
            ->with($clientNonce, 1)
            ->once();

        $this->clientNonceService->shouldReceive('getSignedNonce')
            ->with($clientNonce)
            ->once()
            ->andReturn('signed-nonce-value');

        $this->eventDispatcher->shouldReceive('dispatch')
            ->with(Mockery::type(OAuthLoginSuccess::class))
            ->once();

        $this->viewFactory->shouldReceive('make')
            ->once()
            ->andReturn('test');

        // Act
        $response = $this->action->__invoke($request, $provider);

        // Assert
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->status());
        $this->assertStringContainsString('test', $response->getContent());
    }

    /**
     * Test successful OAuth callback with stateful login.
     */
    public function testSuccessfulOAuthCallbackStateful(): void
    {
        // Arrange
        $provider      = 'google';
        $user          = User::first();
        $socialiteUser = Mockery::mock(SocialiteUser::class);

        $request = Mockery::mock(CallbackRequest::class);
        $request->shouldReceive('validated')
            ->with('state', '')
            ->once()
            ->andReturn('');

        $this->serverTokenService->shouldReceive('getClientNonce')
            ->with('')
            ->once()
            ->andReturn(null);

        $this->socialiteService->shouldReceive('getProviderUser')
            ->with($provider, false)
            ->once()
            ->andReturn($socialiteUser);

        $this->userAuthenticationService->shouldReceive('handleOAuthLogin')
            ->with($provider, $socialiteUser)
            ->once()
            ->andReturn([$user, OAuthStatusEnum::LOGIN_OK]);

        $this->userAuthenticationService->shouldReceive('logInWithId')
            ->with($user->id)
            ->once();

        $this->frontendService->shouldReceive('redirectPage')
            ->with('', ['message' => OAuthStatusEnum::LOGIN_OK->getTranslatedMessage()])
            ->once()
            ->andReturn(new RedirectResponse('/'));

        // Act
        $response = $this->action->__invoke($request, $provider);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    /**
     * Test OAuth callback when OAuthException occurs.
     */
    public function testOAuthCallbackHandlesOAuthException(): void
    {
        // Arrange
        $provider = 'google';

        $request = Mockery::mock(CallbackRequest::class);
        $request->shouldReceive('validated')
            ->with('state', '')
            ->once()
            ->andReturn('');

        $this->serverTokenService->shouldReceive('getClientNonce')
            ->with('')
            ->once()
            ->andReturn(null);

        $this->socialiteService->shouldReceive('getProviderUser')
            ->with($provider, false)
            ->once()
            ->andThrow(new OAuthException(OAuthStatusEnum::LOGIN_OK));

        $this->frontendService->shouldReceive('redirectPage')
            ->with('', ['message' => OAuthStatusEnum::LOGIN_OK->getTranslatedMessage()])
            ->once()
            ->andReturn(new RedirectResponse('/login?error=oauth'));

        // Act
        $response = $this->action->__invoke($request, $provider);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login?error=oauth', $response->getTargetUrl());
    }

    /**
     * Test OAuth callback when a general Exception occurs.
     */
    public function testOAuthCallbackHandlesGeneralException(): void
    {
        // Arrange
        $provider = 'google';

        $request = Mockery::mock(CallbackRequest::class);
        $request->shouldReceive('validated')
            ->with('state', '')
            ->once()
            ->andReturn('');

        $this->serverTokenService->shouldReceive('getClientNonce')
            ->with('')
            ->once()
            ->andReturn(null);

        $this->socialiteService->shouldReceive('getProviderUser')
            ->with($provider, false)
            ->once()
            ->andThrow(new Exception('Something went wrong'));

        $this->frontendService->shouldReceive('redirectPage')
            ->with('', ['message' => 'Something went wrong'])
            ->once()
            ->andReturn(new RedirectResponse('/login?error=general'));

        // Act
        $response = $this->action->__invoke($request, $provider);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login?error=general', $response->getTargetUrl());
    }

    /**
     * Test that `handleFailedLogin` is executed when OAuth login fails.
     */
    public function testOAuthCallbackHandlesFailedLogin(): void
    {
        // Arrange
        $provider      = 'google';
        $signedToken   = 'signed-token-123';
        $clientNonce   = 'nonce-456';
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $failedStatus  = OAuthStatusEnum::EMAIL_NOT_VERIFIED; // Simulating a failed login

        $request = Mockery::mock(CallbackRequest::class);
        $request->shouldReceive('validated')
            ->with('state', '')
            ->once()
            ->andReturn($signedToken);

        $this->serverTokenService->shouldReceive('getClientNonce')
            ->with($signedToken)
            ->once()
            ->andReturn($clientNonce);

        $this->socialiteService->shouldReceive('getProviderUser')
            ->with($provider, true)
            ->once()
            ->andReturn($socialiteUser);

        $this->userAuthenticationService->shouldReceive('handleOAuthLogin')
            ->with($provider, $socialiteUser)
            ->once()
            ->andReturn([null, $failedStatus]);

        $this->frontendService->shouldReceive('redirectPage')
            ->with('', ['message' => $failedStatus->getTranslatedMessage()])
            ->once()
            ->andReturn(new RedirectResponse('/login?error=invalid-credentials'));

        // Act
        $response = $this->action->__invoke($request, $provider);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login?error=invalid-credentials', $response->getTargetUrl());
    }
}
