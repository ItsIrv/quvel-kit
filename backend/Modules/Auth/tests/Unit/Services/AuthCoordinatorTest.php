<?php

namespace Modules\Auth\Tests\Unit\Services;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Mockery;
use Modules\Auth\Enums\OAuthStatusEnum;
use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Services\ClientNonceService;
use Modules\Auth\Services\NonceSessionService;
use Modules\Auth\Services\OAuthCoordinator;
use Modules\Auth\Services\ServerTokenService;
use Modules\Auth\Services\SocialiteService;
use Modules\Auth\Services\UserAuthenticationService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Psr\SimpleCache\InvalidArgumentException;
use Random\RandomException;
use Tests\TestCase;

#[CoversClass(OAuthCoordinator::class)]
#[Group('auth-module')]
#[Group('auth-services')]
class AuthCoordinatorTest extends TestCase
{
    private Mockery\MockInterface|SocialiteService $socialiteService;

    private Mockery\MockInterface|ServerTokenService $serverTokenService;

    private Mockery\MockInterface|ClientNonceService $clientNonceService;

    private Mockery\MockInterface|NonceSessionService $nonceSessionService;

    private Mockery\MockInterface|UserAuthenticationService $userAuthenticationService;

    private OAuthCoordinator $authCoordinator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->socialiteService = Mockery::mock(SocialiteService::class);
        $this->serverTokenService = Mockery::mock(ServerTokenService::class);
        $this->clientNonceService = Mockery::mock(ClientNonceService::class);
        $this->nonceSessionService = Mockery::mock(NonceSessionService::class);
        $this->userAuthenticationService = Mockery::mock(UserAuthenticationService::class);

        $this->authCoordinator = new OAuthCoordinator(
            socialiteService: $this->socialiteService,
            serverTokenService: $this->serverTokenService,
            clientNonceService: $this->clientNonceService,
            nonceSessionService: $this->nonceSessionService,
            userAuthenticationService: $this->userAuthenticationService,
        );
    }

    /**
     * @throws InvalidArgumentException
     * @throws RandomException
     * @throws OAuthException
     */
    public function test_create_client_nonce(): void
    {
        // Arrange
        $nonce = 'generated-nonce';

        $this->clientNonceService->shouldReceive('create')
            ->once()
            ->andReturn($nonce);

        $this->nonceSessionService->shouldReceive('setNonce')
            ->once()
            ->with($nonce);

        // Act
        $result = $this->authCoordinator->createClientNonce();

        // Assert
        $this->assertEquals($nonce, $result);
    }

    /**
     * @throws RandomException
     * @throws InvalidArgumentException
     * @throws OAuthException
     */
    public function test_build_redirect_response_without_nonce(): void
    {
        // Arrange
        $provider = 'google';
        $requestNonce = null; // no nonce passed
        $expectedRedirect = new RedirectResponse('https://example.com/redirect');

        // No calls to clientNonceService or serverTokenService, because $requestNonce is empty
        $this->socialiteService
            ->shouldReceive('getRedirectResponse')
            ->once()
            ->with($provider, '')
            ->andReturn($expectedRedirect);

        // Act
        $response = $this->authCoordinator->buildRedirectResponse($provider, $requestNonce);

        // Assert
        $this->assertSame($expectedRedirect, $response);
    }

    /**
     * @throws InvalidArgumentException
     * @throws RandomException
     * @throws OAuthException
     */
    public function test_build_redirect_response_with_nonce(): void
    {
        // Arrange
        $provider = 'google';
        $requestNonce = 'nonce-123';
        $validatedNonce = 'nonce-123'; // the raw nonce from cache
        $serverToken = 'server-token-xyz';

        $expectedRedirect = new RedirectResponse('https://example.com/oauth?state=token');

        $this->clientNonceService->shouldReceive('getNonce')
            ->once()
            ->with($requestNonce, ClientNonceService::TOKEN_CREATED)
            ->andReturn($validatedNonce);

        $this->clientNonceService->shouldReceive('assignRedirectedToNonce')
            ->once()
            ->with($validatedNonce);

        $this->serverTokenService->shouldReceive('create')
            ->once()
            ->with($validatedNonce)
            ->andReturn($serverToken);

        $this->socialiteService->shouldReceive('getRedirectResponse')
            ->once()
            ->with($provider, $serverToken)
            ->andReturn($expectedRedirect);

        // Act
        $response = $this->authCoordinator->buildRedirectResponse($provider, $requestNonce);

        // Assert
        $this->assertSame($expectedRedirect, $response);
    }

    /**
     * @throws InvalidArgumentException
     * @throws OAuthException
     */
    public function test_authenticate_callback_stateless_login_ok(): void
    {
        // Arrange
        $provider = 'google';
        $signedToken = 'signed-token-123';
        $clientNonce = 'nonce-456'; // non-null => stateless
        $providerUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);

        $user = User::first();
        $status = OAuthStatusEnum::LOGIN_OK;

        $this->serverTokenService->shouldReceive('getClientNonce')
            ->once()
            ->with($signedToken)
            ->andReturn($clientNonce);

        // For a stateless flow, we call getProviderUser($provider, true)
        $this->socialiteService->shouldReceive('getProviderUser')
            ->once()
            ->with($provider, true)
            ->andReturn($providerUser);

        $this->userAuthenticationService->shouldReceive('handleOAuthLogin')
            ->once()
            ->with($provider, $providerUser)
            ->andReturn([$user, $status]);

        // Because status = LOGIN_OK and flow is stateless
        // => completeStatelessLogin
        // => serverTokenService->forget($signedToken)
        // => clientNonceService->assignUserToNonce($clientNonce, $user->id)
        $this->serverTokenService->shouldReceive('forget')
            ->once()
            ->with($signedToken);

        $this->clientNonceService->shouldReceive('assignUserToNonce')
            ->once()
            ->with($clientNonce, 1);

        // We build the result with a "signed nonce"
        $this->clientNonceService->shouldReceive('getSignedNonce')
            ->once()
            ->with($clientNonce)
            ->andReturn('signed-nonce-value');

        // Act
        $result = $this->authCoordinator->authenticateCallback($provider, $signedToken);

        // Assert
        $this->assertEquals($user->id, $result->getUser()->id);
        $this->assertEquals($status, $result->getStatus());
        $this->assertEquals('signed-nonce-value', $result->getSignedNonce());
    }

    /**
     * @throws InvalidArgumentException
     * @throws OAuthException
     */
    public function test_authenticate_callback_stateful_login_ok(): void
    {
        // Arrange
        $provider = 'google';
        $signedToken = 'signed-token-123';
        $clientNonce = null; // => stateful
        $providerUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);

        $user = User::first();
        $status = OAuthStatusEnum::LOGIN_OK;

        $this->serverTokenService->shouldReceive('getClientNonce')
            ->once()
            ->with($signedToken)
            ->andReturn($clientNonce);

        // getProviderUser($provider, false)
        $this->socialiteService->shouldReceive('getProviderUser')
            ->once()
            ->with($provider, false)
            ->andReturn($providerUser);

        $this->userAuthenticationService->shouldReceive('handleOAuthLogin')
            ->once()
            ->with($provider, $providerUser)
            ->andReturn([$user, $status]);

        // Because status=LOGIN_OK and flow is stateful => completeSessionLogin => userAuth->logInWithId
        $this->userAuthenticationService->shouldReceive('logInWithId')
            ->once()
            ->with($user->id);

        // Act
        $result = $this->authCoordinator->authenticateCallback($provider, $signedToken);

        // Assert
        $this->assertEquals($user->id, $result->getUser()->id);
        $this->assertEquals($status, $result->getStatus());
        // For a session-based flow, the "signedNonce" might be null
        $this->assertNull($result->getSignedNonce());
    }

    /**
     * @throws InvalidArgumentException
     * @throws OAuthException
     */
    public function test_authenticate_callback_login_not_ok(): void
    {
        // Arrange
        $provider = 'google';
        $signedToken = 'signed-token-123';
        $clientNonce = 'nonce-456'; // stateless or not doesn't matter if login fails
        $providerUser = Mockery::mock(\Laravel\Socialite\Contracts\User::class);

        $user = User::first();
        $status = OAuthStatusEnum::EMAIL_NOT_VERIFIED;  // e.g., a failure

        $this->serverTokenService->shouldReceive('getClientNonce')
            ->once()
            ->andReturn($clientNonce);

        $this->socialiteService->shouldReceive('getProviderUser')
            ->once()
            ->andReturn($providerUser);

        $this->userAuthenticationService->shouldReceive('handleOAuthLogin')
            ->once()
            ->andReturn([$user, $status]);

        // In this scenario, we do *not* call completeSessionLogin or completeStatelessLogin
        $this->serverTokenService->shouldNotReceive('forget');
        $this->clientNonceService->shouldNotReceive('assignUserToNonce');
        $this->userAuthenticationService->shouldNotReceive('logInWithId');

        $this->clientNonceService->shouldReceive('getSignedNonce')
            ->once()
            ->with($clientNonce)
            ->andReturn('some-signed-nonce'); // it might be used even if failed

        // Act
        $result = $this->authCoordinator->authenticateCallback($provider, $signedToken);

        // Assert
        $this->assertEquals($status, $result->getStatus());
        $this->assertEquals('some-signed-nonce', $result->getSignedNonce());
    }

    /**
     * @throws InvalidArgumentException
     * @throws OAuthException
     */
    public function test_redeem_client_nonce(): void
    {
        // Arrange
        $requestNonce = 'signed-nonce-789';
        $rawNonce = 'nonce-789';
        $user = User::first();
        $userId = $user->id;

        $this->clientNonceService->shouldReceive('getNonce')
            ->once()
            ->with($requestNonce)
            ->andReturn($rawNonce);

        $this->clientNonceService->shouldReceive('getUserIdFromNonce')
            ->once()
            ->with($rawNonce)
            ->andReturn($userId);

        $this->clientNonceService->shouldReceive('forget')
            ->once()
            ->with($rawNonce);

        $this->userAuthenticationService->shouldReceive('logInWithId')
            ->once()
            ->with($userId)
            ->andReturn($user);

        // Act
        $actualUser = $this->authCoordinator->redeemClientNonce($requestNonce);

        // Assert
        $this->assertEquals($userId, $actualUser->id);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function test_redeem_client_nonce_no_user(): void
    {
        // Arrange
        $requestNonce = 'signed-nonce-789';
        $rawNonce = 'nonce-789';

        $this->clientNonceService->shouldReceive('getNonce')
            ->once()
            ->with($requestNonce)
            ->andReturn($rawNonce);

        $this->clientNonceService->shouldReceive('getUserIdFromNonce')
            ->once()
            ->with($rawNonce)
            ->andReturn(null);

        $this->clientNonceService->shouldReceive('forget')
            ->once()
            ->with($rawNonce);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INTERNAL_ERROR->value);

        // Act
        $actualUser = $this->authCoordinator->redeemClientNonce($requestNonce);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function test_redeem_client_nonce_user_not_user(): void
    {
        // Arrange
        $requestNonce = 'signed-nonce-789';
        $rawNonce = 'nonce-789';

        $this->clientNonceService->shouldReceive('getNonce')
            ->once()
            ->with($requestNonce)
            ->andReturn($rawNonce);

        $this->clientNonceService->shouldReceive('getUserIdFromNonce')
            ->once()
            ->with($rawNonce)
            ->andReturn(1);

        $this->clientNonceService->shouldReceive('forget')
            ->once()
            ->with($rawNonce);

        $this->userAuthenticationService->shouldReceive('logInWithId')
            ->once()
            ->with(1)
            ->andReturn(false);

        $this->expectException(OAuthException::class);
        $this->expectExceptionMessage(OAuthStatusEnum::INTERNAL_ERROR->value);

        // Act
        $actualUser = $this->authCoordinator->redeemClientNonce($requestNonce);
    }
}
