<?php

namespace Modules\Auth\Tests\Unit\Http\Requests;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Mockery;
use Modules\Auth\Http\Requests\EmailNotificationRequest;
use Modules\Core\Services\FrontendService;
use Modules\Core\Services\User\UserFindService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(EmailNotificationRequest::class)]
#[Group('auth-module')]
#[Group('auth-requests')]
class EmailNotificationRequestTest extends TestCase
{
    private Mockery\MockInterface $frontendService;
    private Mockery\MockInterface $userFindService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->frontendService = Mockery::mock(FrontendService::class);
        $this->userFindService = Mockery::mock(UserFindService::class);

        $this->app->instance(FrontendService::class, $this->frontendService);
        $this->app->instance(UserFindService::class, $this->userFindService);
    }

    public function testAuthorizeReturnsTrueWhenPreLoginModeIsEnabled(): void
    {
        // Set config for pre-login mode
        config(['auth.verify_email_before_login' => true]);

        $request = new EmailNotificationRequest();

        $this->assertTrue($request->authorize());
    }

    public function testAuthorizeRedirectsWhenUserNotAuthenticated(): void
    {
        // Set config for post-login mode
        config(['auth.verify_email_before_login' => false]);

        $request = Mockery::mock(EmailNotificationRequest::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $request->shouldReceive('user')
            ->once()
            ->andReturn(null);

        $this->frontendService->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('/'));

        $this->expectException(HttpResponseException::class);
        $request->authorize();
    }

    public function testAuthorizeReturnsTrueWhenUserIsAuthenticated(): void
    {
        // Set config for post-login mode
        config(['auth.verify_email_before_login' => false]);

        $user = Mockery::mock(User::class);

        $request = Mockery::mock(EmailNotificationRequest::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $request->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $this->assertTrue($request->authorize());

        // Verify that the user is set as the resolvedUser property
        $this->assertSame($user, $this->getProtectedProperty($request, 'resolvedUser'));
    }

    public function testRulesReturnsEmailValidationRulesInPreLoginMode(): void
    {
        // Set config for pre-login mode
        config(['auth.verify_email_before_login' => true]);

        $request = new EmailNotificationRequest();
        $request->authorize(); // This sets the preLoginMode property

        $rules = $request->rules();

        $this->assertArrayHasKey('email', $rules);
        $this->assertEquals(['required', 'email'], $rules['email']);
    }

    public function testRulesReturnsEmptyArrayInPostLoginMode(): void
    {
        // Set config for post-login mode
        config(['auth.verify_email_before_login' => false]);

        $user = Mockery::mock(User::class);

        $request = Mockery::mock(EmailNotificationRequest::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $request->shouldReceive('user')
            ->once()
            ->andReturn($user);

        $request->authorize(); // This sets the preLoginMode property

        $rules = $request->rules();

        $this->assertEmpty($rules);
    }

    public function testFulfillSendsVerificationEmailInPreLoginMode(): void
    {
        // Set config for pre-login mode
        config(['auth.verify_email_before_login' => true]);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasVerifiedEmail')
            ->once()
            ->andReturn(false);
        $user->shouldReceive('sendEmailVerificationNotification')
            ->once();

        $request = Mockery::mock(EmailNotificationRequest::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $request->shouldReceive('input')
            ->with('email')
            ->andReturn('test@example.com');

        $this->userFindService->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn($user);

        // Set preLoginMode property
        $this->setProtectedProperty($request, 'preLoginMode', true);

        $request->fulfill();
    }

    public function testFulfillSendsVerificationEmailInPostLoginMode(): void
    {
        // Set config for post-login mode
        config(['auth.verify_email_before_login' => false]);

        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasVerifiedEmail')
            ->once()
            ->andReturn(false);
        $user->shouldReceive('sendEmailVerificationNotification')
            ->once();

        $request = Mockery::mock(EmailNotificationRequest::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Set resolvedUser property
        $this->setProtectedProperty($request, 'resolvedUser', $user);
        $this->setProtectedProperty($request, 'preLoginMode', false);

        $request->fulfill();
    }

    public function testFulfillDoesNotSendEmailWhenUserHasVerifiedEmail(): void
    {
        $user = Mockery::mock(User::class);
        $user->shouldReceive('hasVerifiedEmail')
            ->once()
            ->andReturn(true);
        $user->shouldNotReceive('sendEmailVerificationNotification');

        $request = Mockery::mock(EmailNotificationRequest::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        // Set resolvedUser property
        $this->setProtectedProperty($request, 'resolvedUser', $user);
        $this->setProtectedProperty($request, 'preLoginMode', false);

        $request->fulfill();
    }

    public function testFulfillDoesNothingWhenUserNotFound(): void
    {
        // Set config for pre-login mode
        config(['auth.verify_email_before_login' => true]);

        $request = Mockery::mock(EmailNotificationRequest::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $request->shouldReceive('input')
            ->with('email')
            ->andReturn('test@example.com');

        $this->userFindService->shouldReceive('findByEmail')
            ->with('test@example.com')
            ->once()
            ->andReturn(null);

        // Set preLoginMode property
        $this->setProtectedProperty($request, 'preLoginMode', true);

        // This should not throw any exceptions
        $request->fulfill();
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
