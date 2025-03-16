<?php

namespace Modules\Auth\Tests\Unit\Actions\Socialite;

use App\Services\FrontendService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Mockery;
use Modules\Auth\Actions\Socialite\RedirectAction;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedirectRequest;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Psr\SimpleCache\InvalidArgumentException;
use Tests\TestCase;

#[CoversClass(RedirectAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class RedirectActionTest extends TestCase
{
    private Mockery\MockInterface|SocialiteService $socialiteService;

    private Mockery\MockInterface|ServerTokenService $serverTokenService;

    private Mockery\MockInterface|ClientNonceService $clientNonceService;

    private Mockery\MockInterface|FrontendService $frontendService;

    private RedirectAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock Dependencies
        $this->socialiteService = Mockery::mock(SocialiteService::class);
        $this->serverTokenService = Mockery::mock(ServerTokenService::class);
        $this->clientNonceService = Mockery::mock(ClientNonceService::class);
        $this->frontendService = Mockery::mock(FrontendService::class);

        // Instantiate the action with mocks
        $this->action = new RedirectAction(
            $this->socialiteService,
            $this->serverTokenService,
            $this->clientNonceService,
            $this->frontendService,
        );
    }

    /**
     * Test that stateful OAuth redirect works.
     *
     * @throws InvalidArgumentException
     */
    public function test_stateful_o_auth_redirect(): void
    {
        // Arrange
        $provider = 'google';

        $request = Mockery::mock(RedirectRequest::class);
        $request->shouldReceive('has')
            ->with('nonce')
            ->once()
            ->andReturn(false);

        $this->socialiteService->shouldReceive('getRedirectResponse')
            ->with($provider)
            ->once()
            ->andReturn(new RedirectResponse('https://google.com/oauth'));

        // Act
        $response = $this->action->__invoke($request, $provider);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://google.com/oauth', $response->getTargetUrl());
    }

    /**
     * Test that stateless OAuth redirect works.
     *
     * @throws InvalidArgumentException
     */
    public function test_stateless_o_auth_redirect(): void
    {
        // Arrange
        $provider = 'google';
        $nonce = 'nonce-value';
        $token = 'signed-token';

        $request = Mockery::mock(RedirectRequest::class);
        $request->shouldReceive('has')
            ->with('nonce')
            ->once()
            ->andReturn(true);

        $request->shouldReceive('validated')
            ->with('nonce')
            ->once()
            ->andReturn($nonce);

        $this->clientNonceService->shouldReceive('getNonce')
            ->with($nonce, ClientNonceService::TOKEN_CREATED)
            ->once()
            ->andReturn($nonce);

        $this->clientNonceService->shouldReceive('assignRedirectedToNonce')
            ->with($nonce)
            ->once();

        $this->serverTokenService->shouldReceive('create')
            ->with($nonce)
            ->once()
            ->andReturn($token);

        $this->socialiteService->shouldReceive('getRedirectResponse')
            ->with($provider, $token)
            ->once()
            ->andReturn(new RedirectResponse("https://google.com/oauth?state=$token"));

        // Act
        $response = $this->action->__invoke($request, $provider);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals("https://google.com/oauth?state=$token", $response->getTargetUrl());
    }

    /**
     * Test that an OAuthException is handled correctly.
     *
     * @throws InvalidArgumentException
     */
    public function test_o_auth_redirect_handles_o_auth_exception(): void
    {
        // Arrange
        $provider = 'google';

        $request = Mockery::mock(RedirectRequest::class);
        $request->shouldReceive('has')
            ->with('nonce')
            ->once()
            ->andReturn(false);

        // Get the actual error message from the exception
        $exception = new OAuthException(OAuthStatusEnum::INVALID_CONFIG);
        $expectedErrorMessage = $exception->getTranslatedMessage();

        $this->socialiteService->shouldReceive('getRedirectResponse')
            ->with($provider)
            ->once()
            ->andThrow($exception);

        $this->frontendService->shouldReceive('redirectPage')
            ->with('', ['message' => $expectedErrorMessage])
            ->once()
            ->andReturn(new RedirectResponse('/login?error=oauth'));

        // Act
        $response = $this->action->__invoke($request, $provider);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('/login?error=oauth', $response->getTargetUrl());
    }

    /**
     * Test that a general Exception is handled correctly.
     *
     * @throws InvalidArgumentException
     */
    public function test_o_auth_redirect_handles_general_exception(): void
    {
        // Arrange
        $provider = 'google';

        $request = Mockery::mock(RedirectRequest::class);
        $request->shouldReceive('has')
            ->with('nonce')
            ->once()
            ->andReturn(false);

        $this->socialiteService->shouldReceive('getRedirectResponse')
            ->with($provider)
            ->once()
            ->andThrow(new Exception('Something went wrong'));

        $this->frontendService->shouldReceive('redirectPage')
            ->with('', ['message' => 'Something went wrong'])
            ->once()
            ->andReturn(new RedirectResponse('/login?error=oauth'));

        // Act
        $response = $this->action->__invoke($request, $provider);

        // Assert
        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals(302, $response->status());
        $this->assertEquals('/login?error=oauth', $response->getTargetUrl());
    }
}
