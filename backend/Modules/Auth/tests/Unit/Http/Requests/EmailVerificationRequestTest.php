<?php

namespace Modules\Auth\Tests\Unit\Http\Requests;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Route;
use Mockery;
use Modules\Auth\Http\Requests\EmailVerificationRequest;
use Modules\Auth\Services\UserAuthenticationService;
use Modules\Core\Services\FrontendService;
use Modules\Core\Services\User\UserFindService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(EmailVerificationRequest::class)]
#[Group('auth-module')]
#[Group('auth-requests')]
class EmailVerificationRequestTest extends TestCase
{
    private Mockery\MockInterface $frontendService;
    private Mockery\MockInterface $userFindService;
    private Mockery\MockInterface $userAuthService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->frontendService = Mockery::mock(FrontendService::class);
        $this->userFindService = Mockery::mock(UserFindService::class);
        $this->userAuthService = Mockery::mock(UserAuthenticationService::class);

        $this->app->instance(FrontendService::class, $this->frontendService);
        $this->app->instance(UserFindService::class, $this->userFindService);
        $this->app->instance(UserAuthenticationService::class, $this->userAuthService);
    }

    /**
     * Test authorization in pre-login mode with valid data.
     */
    public function testAuthorizeReturnsTrueInPreLoginModeWithValidData(): void
    {
        // Set config for pre-login mode
        config(['auth.verify_email_before_login' => true]);

        $publicId = 'user-123';
        $email    = 'test@example.com';
        $hash     = sha1($email);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getEmailForVerification')
            ->once()
            ->andReturn($email);

        $this->userFindService->shouldReceive('findByPublicId')
            ->with($publicId)
            ->once()
            ->andReturn($user);

        $request = $this->createRequestWithRoute($publicId, $hash);

        $this->assertTrue($request->authorize());

        // Verify that the user is set as the verificationUser property
        $this->assertSame($user, $this->getProtectedProperty($request, 'verificationUser'));
    }

    /**
     * Test authorization in pre-login mode with invalid hash.
     */
    public function testAuthorizeReturnsFalseInPreLoginModeWithInvalidHash(): void
    {
        // Set config for pre-login mode
        config(['auth.verify_email_before_login' => true]);

        $publicId = 'user-123';
        $email    = 'test@example.com';
        $hash     = 'invalid-hash'; // Invalid hash

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getEmailForVerification')
            ->once()
            ->andReturn($email);

        $this->userFindService->shouldReceive('findByPublicId')
            ->with($publicId)
            ->once()
            ->andReturn($user);

        $request = $this->createRequestWithRoute($publicId, $hash);

        $this->assertFalse($request->authorize());
    }

    /**
     * Test authorization in pre-login mode with user not found.
     */
    public function testAuthorizeReturnsFalseInPreLoginModeWithUserNotFound(): void
    {
        // Set config for pre-login mode
        config(['auth.verify_email_before_login' => true]);

        $publicId = 'user-123';
        $hash     = 'some-hash';

        $this->userFindService->shouldReceive('findByPublicId')
            ->with($publicId)
            ->once()
            ->andReturn(null);

        $request = $this->createRequestWithRoute($publicId, $hash);

        $this->assertFalse($request->authorize());
    }

    /**
     * Test authorization in pre-login mode with exception thrown.
     */
    public function testAuthorizeReturnsFalseInPreLoginModeWithException(): void
    {
        // Set config for pre-login mode
        config(['auth.verify_email_before_login' => true]);

        $publicId = 'user-123';
        $hash     = 'some-hash';

        $this->userFindService->shouldReceive('findByPublicId')
            ->with($publicId)
            ->once()
            ->andThrow(new \Exception('Test exception'));

        $request = $this->createRequestWithRoute($publicId, $hash);

        $this->assertFalse($request->authorize());
    }

    /**
     * Test authorization in post-login mode with user not authenticated.
     */
    public function testAuthorizeRedirectsInPostLoginModeWhenUserNotAuthenticated(): void
    {
        // Set config for post-login mode
        config(['auth.verify_email_before_login' => false]);

        $publicId = 'user-123';
        $hash     = 'some-hash';

        $this->userAuthService->shouldReceive('check')
            ->once()
            ->andReturn(false);

        $this->frontendService->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('/'));

        $request = $this->createRequestWithRoute($publicId, $hash);

        $this->expectException(HttpResponseException::class);
        $request->authorize();
    }

    /**
     * Test authorization in post-login mode with valid data.
     */
    public function testAuthorizeReturnsTrueInPostLoginModeWithValidData(): void
    {
        // Set config for post-login mode
        config(['auth.verify_email_before_login' => false]);

        $publicId = 'user-123';
        $email    = 'test@example.com';
        $hash     = sha1($email);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('public_id')
            ->andReturn($publicId);
        $user->shouldReceive('__get')
            ->with('public_id')
            ->andReturn($publicId);
        $user->shouldReceive('getEmailForVerification')
            ->once()
            ->andReturn($email);
        $user->shouldReceive('setAttribute')
            ->zeroOrMoreTimes();
        $user->shouldReceive('offsetExists')
            ->zeroOrMoreTimes()
            ->andReturn(true);

        $this->userAuthService->shouldReceive('check')
            ->once()
            ->andReturn(true);

        $request = $this->createRequestWithRoute($publicId, $hash);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $this->assertTrue($request->authorize());

        // Verify that the user is set as the verificationUser property
        $this->assertSame($user, $this->getProtectedProperty($request, 'verificationUser'));
    }

    /**
     * Test authorization in post-login mode with invalid public ID.
     */
    public function testAuthorizeReturnsFalseInPostLoginModeWithInvalidPublicId(): void
    {
        // Set config for post-login mode
        config(['auth.verify_email_before_login' => false]);

        $publicId     = 'user-123';
        $userPublicId = 'user-456'; // Different public ID
        $hash         = 'some-hash';

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('public_id')
            ->andReturn($userPublicId);
        $user->shouldReceive('__get')
            ->with('public_id')
            ->andReturn($userPublicId);
        $user->shouldReceive('setAttribute')
            ->zeroOrMoreTimes();
        $user->shouldReceive('offsetExists')
            ->zeroOrMoreTimes()
            ->andReturn(true);

        $this->userAuthService->shouldReceive('check')
            ->once()
            ->andReturn(true);

        $request = $this->createRequestWithRoute($publicId, $hash);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $this->assertFalse($request->authorize());
    }

    /**
     * Test authorization in post-login mode with invalid hash.
     */
    public function testAuthorizeReturnsFalseInPostLoginModeWithInvalidHash(): void
    {
        // Set config for post-login mode
        config(['auth.verify_email_before_login' => false]);

        $publicId = 'user-123';
        $email    = 'test@example.com';
        $hash     = 'invalid-hash'; // Invalid hash

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('public_id')
            ->andReturn($publicId);
        $user->shouldReceive('__get')
            ->with('public_id')
            ->andReturn($publicId);
        $user->shouldReceive('getEmailForVerification')
            ->once()
            ->andReturn($email);
        $user->shouldReceive('setAttribute')
            ->zeroOrMoreTimes();
        $user->shouldReceive('offsetExists')
            ->zeroOrMoreTimes()
            ->andReturn(true);

        $this->userAuthService->shouldReceive('check')
            ->once()
            ->andReturn(true);

        $request = $this->createRequestWithRoute($publicId, $hash);
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $this->assertFalse($request->authorize());
    }

    /**
     * Test fulfill marks email as verified.
     */
    public function testFulfillMarksEmailAsVerified(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasVerifiedEmail')
            ->once()
            ->andReturn(false);
        $user->shouldReceive('markEmailAsVerified')
            ->once();

        $request = new EmailVerificationRequest();

        // Set verificationUser property
        $this->setProtectedProperty($request, 'verificationUser', $user);

        $request->fulfill();
    }

    /**
     * Test fulfill does nothing when user already verified.
     */
    public function testFulfillDoesNothingWhenUserAlreadyVerified(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasVerifiedEmail')
            ->once()
            ->andReturn(true);
        $user->shouldNotReceive('markEmailAsVerified');

        $request = new EmailVerificationRequest();

        // Set verificationUser property
        $this->setProtectedProperty($request, 'verificationUser', $user);

        $request->fulfill();
    }

    /**
     * Test fulfill does nothing when user is null.
     */
    public function testFulfillDoesNothingWhenUserIsNull(): void
    {
        $request = new EmailVerificationRequest();

        // Set verificationUser property to null
        $this->setProtectedProperty($request, 'verificationUser', null);

        // This should not throw any exceptions
        $request->fulfill();

        // Add an assertion to avoid risky test warning
        $this->assertTrue(true);
    }

    /**
     * Helper method to create a request with route parameters.
     */
    private function createRequestWithRoute(string $id, string $hash): EmailVerificationRequest
    {
        $request = new EmailVerificationRequest();

        $route             = new Route('GET', 'email/verify/{id}/{hash}', []);
        $route->parameters = ['id' => $id, 'hash' => $hash];

        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        return $request;
    }

    /**
     * Helper method to get protected property value.
     */
    private function getProtectedProperty(object $object, string $property)
    {
        $reflection = new \ReflectionClass($object);
        $property   = $reflection->getProperty($property);
        $property->setAccessible(true);
        return $property->getValue($object);
    }

    /**
     * Helper method to set protected property value.
     */
    private function setProtectedProperty(object $object, string $property, $value): void
    {
        $reflection = new \ReflectionClass($object);
        $property   = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
