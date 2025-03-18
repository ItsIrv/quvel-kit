<?php

namespace Modules\Auth\Tests\Unit\Actions\Socialite;

use Illuminate\Http\RedirectResponse;
use Mockery;
use Modules\Auth\Actions\Socialite\RedirectAction;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Http\Requests\RedirectRequest;
use Modules\Auth\Services\AuthCoordinator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(RedirectAction::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class RedirectActionTest extends TestCase
{
    private Mockery\MockInterface|AuthCoordinator $authCoordinator;

    private RedirectAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authCoordinator = Mockery::mock(AuthCoordinator::class);
        $this->action = new RedirectAction($this->authCoordinator);
    }

    public function test_redirect_action_with_nonce(): void
    {
        // Arrange
        $provider = 'google';
        $nonce = 'nonce-value';
        $mockReq = Mockery::mock(RedirectRequest::class);
        $expectedRedirect = new RedirectResponse('https://example.com/with-nonce');

        // The request should return the validated nonce
        $mockReq->shouldReceive('validated')
            ->with('nonce')
            ->once()
            ->andReturn($nonce);

        // Coordinator should get called with the provider + nonce
        $this->authCoordinator
            ->shouldReceive('buildRedirectResponse')
            ->once()
            ->with($provider, $nonce)
            ->andReturn($expectedRedirect);

        // Act
        $response = $this->action->__invoke($mockReq, $provider);

        // Assert
        $this->assertSame($expectedRedirect, $response);
    }

    public function test_redirect_action_without_nonce(): void
    {
        // Arrange
        $provider = 'google';
        $mockReq = Mockery::mock(RedirectRequest::class);
        $expectedRedirect = new RedirectResponse('https://example.com/no-nonce');

        // The request might return null if 'nonce' is not present
        $mockReq->shouldReceive('validated')
            ->with('nonce')
            ->once()
            ->andReturn(null);

        // Coordinator should get called with provider + null
        $this->authCoordinator
            ->shouldReceive('buildRedirectResponse')
            ->once()
            ->with($provider, null)
            ->andReturn($expectedRedirect);

        // Act
        $response = $this->action->__invoke($mockReq, $provider);

        // Assert
        $this->assertSame($expectedRedirect, $response);
    }

    public function test_oauth_exception_is_thrown_as_is(): void
    {
        // Arrange
        $provider = 'google';
        $mockReq = Mockery::mock(RedirectRequest::class);
        $mockReq->shouldReceive('validated')->andReturn(null);

        $oauthEx = new OAuthException(OAuthStatusEnum::INVALID_CONFIG);

        // The coordinator throws an OAuthException
        $this->authCoordinator
            ->shouldReceive('buildRedirectResponse')
            ->once()
            ->andThrow($oauthEx);

        // Expect the exact same exception to bubble up
        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INVALID_CONFIG->value);

        // Act
        $this->action->__invoke($mockReq, $provider);
    }

    public function test_general_exception_is_wrapped_in_oauth_exception(): void
    {
        // Arrange
        $provider = 'google';
        $mockReq = Mockery::mock(RedirectRequest::class);
        $mockReq->shouldReceive('validated')->andReturn(null);

        $generalEx = new \Exception('Some general error');

        $this->authCoordinator
            ->shouldReceive('buildRedirectResponse')
            ->once()
            ->andThrow($generalEx);

        // We'll verify that the action wraps it in an OAuthException
        $this->expectException(OAuthException::class);
        // The message will be OAuthStatusEnum::INTERNAL_ERROR->value
        $this->expectExceptionMessage(OAuthStatusEnum::INTERNAL_ERROR->value);

        // Act
        $this->action->__invoke($mockReq, $provider);
    }
}
