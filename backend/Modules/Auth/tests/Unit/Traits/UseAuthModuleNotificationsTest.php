<?php

namespace Modules\Auth\Tests\Unit\Traits;

use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Modules\Auth\Notifications\ResetPassword;
use Modules\Auth\Notifications\VerifyEmail;
use Modules\Auth\Traits\UseAuthModuleNotifications;
use Modules\Core\Enums\StatusEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

#[CoversClass(UseAuthModuleNotifications::class)]
#[Group('auth-module')]
#[Group('auth-traits')]
class UseAuthModuleNotificationsTest extends TestCase
{
    use WithFaker;

    /**
     * Test that sendEmailVerificationNotification sends the correct notification.
     */
    public function testSendEmailVerificationNotification(): void
    {
        // Arrange
        Notification::fake();

        // Create a test user that uses the trait
        $user = new class extends User
        {
            use UseAuthModuleNotifications;
        };

        // Act
        $user->sendEmailVerificationNotification();

        // Assert
        Notification::assertSentTo(
            $user,
            VerifyEmail::class,
        );
    }

    /**
     * Test that sendPasswordResetNotification sends the correct notification.
     */
    public function testSendPasswordResetNotification(): void
    {
        // Arrange
        Notification::fake();
        $token = 'test-token';

        // Create a test user that uses the trait
        $user = new class extends User
        {
            use UseAuthModuleNotifications;
        };

        // Act
        $user->sendPasswordResetNotification($token);

        // Assert
        Notification::assertSentTo(
            $user,
            function (ResetPassword $notification, $channels) use ($token) {
                return $notification->token === $token;
            }
        );
    }

    /**
     * Test that sendPasswordResetNotification aborts when user has a provider_id.
     */
    public function testSendPasswordResetNotificationAbortsWithProviderId(): void
    {
        // Arrange
        $token = 'test-token';

        // Create a test user that uses the trait
        $user              = new class extends User
        {
            use UseAuthModuleNotifications;
        };
        $user->provider_id = 'oauth-provider';

        // Expect an exception
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage(StatusEnum::INTERNAL_ERROR->value);

        // Act
        $user->sendPasswordResetNotification($token);
    }
}
