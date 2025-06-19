<?php

namespace Modules\Auth\Tests\Unit\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Mockery;
use Modules\Auth\Notifications\ResetPassword;
use Modules\Core\Services\FrontendService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(ResetPassword::class)]
#[Group('auth-module')]
#[Group('auth-notifications')]
class ResetPasswordTest extends TestCase
{
    /**
     * Test that the notification builds the correct mail message.
     */
    public function testBuildMailMessageReturnsCorrectMessage(): void
    {
        // Arrange
        $token       = 'test-token';
        $frontendUrl = 'https://example.com/reset-password?token=test-token&form=password-reset';

        $frontendService = Mockery::mock(FrontendService::class);
        $frontendService->shouldReceive('getPageUrl')
            ->once()
            ->with('', [
                'form'  => 'password-reset',
                'token' => $token,
            ])
            ->andReturn($frontendUrl);

        $this->app->instance(FrontendService::class, $frontendService);

        // Set config values needed for the notification
        config(['app.name' => 'Test App']);
        config(['auth.passwords.users.expire' => 60]);
        config(['auth.defaults.passwords' => 'users']);

        // Create the notification
        $notification = new ResetPassword($token);

        // Use reflection to access the protected method
        $reflectionMethod = new \ReflectionMethod(ResetPassword::class, 'buildMailMessage');
        $reflectionMethod->setAccessible(true);

        // Act
        $mailMessage = $reflectionMethod->invoke($notification, $frontendUrl);

        // Assert
        $this->assertInstanceOf(MailMessage::class, $mailMessage);

        // Get the data array from the mail message to verify its content
        $mailData = $this->getMailData($mailMessage);

        $this->assertEquals(__('Reset Your Password'), $mailData['subject']);
        $this->assertEquals(__('Hello,'), $mailData['greeting']);
        $this->assertStringContainsString(__('You requested to reset the password for your account on Test App.'), $mailData['introLines'][0]);
        $this->assertEquals(__('Reset Password'), $mailData['actionText']);
        $this->assertEquals($frontendUrl, $mailData['actionUrl']);
        $this->assertStringContainsString(__('This link will expire in 60 minutes.'), $mailData['outroLines'][0]);
        $this->assertStringContainsString(__('If you did not request a password reset'), $mailData['outroLines'][1]);
        $this->assertStringContainsString(__('Thank you,'), $mailData['salutation']);
        $this->assertStringContainsString('Test App Team', $mailData['salutation']);
    }

    /**
     * Helper method to extract the data from a MailMessage instance.
     */
    private function getMailData(MailMessage $mailMessage): array
    {
        $reflection = new \ReflectionClass($mailMessage);

        $data       = [];
        $properties = [
            'subject',
            'greeting',
            'introLines',
            'outroLines',
            'actionText',
            'actionUrl',
            'salutation',
        ];

        foreach ($properties as $property) {
            $prop = $reflection->getProperty($property);
            $prop->setAccessible(true);
            $data[$property] = $prop->getValue($mailMessage);
        }

        return $data;
    }
}
