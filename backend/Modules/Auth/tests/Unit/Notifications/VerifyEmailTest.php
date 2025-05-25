<?php

namespace Modules\Auth\Tests\Unit\Notifications;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Mockery;
use Modules\Auth\Notifications\VerifyEmail;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(VerifyEmail::class)]
#[Group('auth-module')]
#[Group('auth-notifications')]
class VerifyEmailTest extends TestCase
{
    /**
     * Test that the verification URL uses the public_id when available.
     */
    public function testVerificationUrlUsesPublicIdWhenAvailable(): void
    {
        // Arrange
        $publicId    = 'user-123';
        $email       = 'test@example.com';
        $expectedUrl = 'https://example.com/verify-email/user-123/hash';

        // Mock the URL facade
        URL::shouldReceive('temporarySignedRoute')
            ->once()
            ->withArgs(function ($name, $expiration, $parameters) use ($publicId) {
                return $name === 'verification.verify' &&
                    $parameters['id'] === $publicId &&
                    isset($parameters['hash']);
            })
            ->andReturn($expectedUrl);

        // Mock the user model
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getEmailForVerification')
            ->once()
            ->andReturn($email);
        $user->shouldReceive('offsetExists')
            ->zeroOrMoreTimes()
            ->with('public_id')
            ->andReturn(true);
        $user->shouldReceive('getAttribute')
            ->zeroOrMoreTimes()
            ->with('public_id')
            ->andReturn($publicId);

        // Set the verification expiration time
        config(['auth.verification.expire' => 60]);

        // Create the notification
        $notification = new VerifyEmail();

        // Use reflection to access the protected method
        $reflectionMethod = new \ReflectionMethod(VerifyEmail::class, 'verificationUrl');
        $reflectionMethod->setAccessible(true);

        // Act
        $url = $reflectionMethod->invoke($notification, $user);

        // Assert
        $this->assertEquals($expectedUrl, $url);
    }

    /**
     * Test that the verification URL falls back to the primary key when public_id is not available.
     */
    public function testVerificationUrlFallsBackToPrimaryKeyWhenPublicIdNotAvailable(): void
    {
        // Arrange
        $primaryKey  = 123;
        $email       = 'test@example.com';
        $expectedUrl = 'https://example.com/verify-email/123/hash';

        // Mock the URL facade
        URL::shouldReceive('temporarySignedRoute')
            ->once()
            ->withArgs(function ($name, $expiration, $parameters) use ($primaryKey) {
                return $name === 'verification.verify' &&
                    $parameters['id'] === $primaryKey &&
                    isset($parameters['hash']);
            })
            ->andReturn($expectedUrl);

        // Mock the user model
        $user = Mockery::mock(User::class);
        $user->shouldReceive('getKey')
            ->once()
            ->andReturn($primaryKey);
        $user->shouldReceive('getEmailForVerification')
            ->once()
            ->andReturn($email);
        $user->shouldReceive('offsetExists')
            ->zeroOrMoreTimes()
            ->with('public_id')
            ->andReturn(false);
        $user->shouldReceive('getAttribute')
            ->zeroOrMoreTimes()
            ->with('public_id')
            ->andReturn(null);

        // Set the verification expiration time
        config(['auth.verification.expire' => 60]);

        // Create the notification
        $notification = new VerifyEmail();

        // Use reflection to access the protected method
        $reflectionMethod = new \ReflectionMethod(VerifyEmail::class, 'verificationUrl');
        $reflectionMethod->setAccessible(true);

        // Act
        $url = $reflectionMethod->invoke($notification, $user);

        // Assert
        $this->assertEquals($expectedUrl, $url);
    }
}
