<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Modules\Auth\Actions\Fortify\UpdateUserProfileInformation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(UpdateUserProfileInformation::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class UpdateUserProfileInformationTest extends TestCase
{
    /**
     * Test that the updateVerifiedUser method correctly resets email verification status.
     */
    public function testUpdateVerifiedUserResetsEmailVerification(): void
    {
        // Create a mock for the validator
        $validatorMock = $this->createMock(ValidationFactory::class);
        
        // Create the action with mocked dependencies
        $action = new UpdateUserProfileInformation($validatorMock);

        // Create a mock user that implements MustVerifyEmail
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['forceFill', 'save', 'sendEmailVerificationNotification'])
            ->getMock();

        // Set up the user's current email
        $user->email = 'old@example.com';

        // Set expectations for the user mock
        $user->expects($this->once())
            ->method('forceFill')
            ->with([
                'name'              => 'John Doe',
                'email'             => 'new@example.com',
                'email_verified_at' => null,
            ])
            ->willReturnSelf();

        $user->expects($this->once())
            ->method('save');

        $user->expects($this->once())
            ->method('sendEmailVerificationNotification');

        // Input data with a new email
        $input = [
            'name'  => 'John Doe',
            'email' => 'new@example.com',
        ];

        // Use reflection to access the protected method
        $reflectionMethod = new \ReflectionMethod(UpdateUserProfileInformation::class, 'updateVerifiedUser');
        $reflectionMethod->setAccessible(true);

        // Call the protected method directly
        $reflectionMethod->invoke($action, $user, $input);

        // Assertions are handled by the mock expectations
    }
}
