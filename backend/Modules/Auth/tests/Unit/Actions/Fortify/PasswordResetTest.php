<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mockery;
use Modules\Auth\Actions\Fortify\PasswordReset;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(PasswordReset::class)]
#[Group('auth-module')]
#[Group('auth-fortify')]
class PasswordResetTest extends TestCase
{
    public function testResetUpdatesUserPasswordWhenValid(): void
    {
        // Mock the validator to pass validation
        $validatorMock = Mockery::mock('Illuminate\Validation\Validator');
        $validatorMock->shouldReceive('validate')->andReturn(true);

        Validator::shouldReceive('make')
            ->once()
            ->withAnyArgs()
            ->andReturn($validatorMock);

        // Mock Hash facade
        Hash::shouldReceive('make')
            ->once()
            ->with('new-password')
            ->andReturn('hashed-password');

        // Create a mock user
        $user = Mockery::mock(User::class);
        $user->shouldReceive('forceFill')
            ->once()
            ->with(['password' => 'hashed-password'])
            ->andReturnSelf();

        $user->shouldReceive('save')
            ->once()
            ->andReturn(true);

        // Create the action
        $action = new PasswordReset();

        // Call the action
        $action->reset($user, ['password' => 'new-password']);

        // Assertions are handled by the mock expectations
    }

    /**
     * Test that the password reset action throws validation exception when password is invalid.
     */
    public function testResetThrowsValidationExceptionWhenPasswordInvalid(): void
    {
        $this->expectException(ValidationException::class);

        // Create a ValidationException with proper structure
        $validationException = ValidationException::withMessages([
            'password' => ['The password is invalid'],
        ]);

        // Mock the validator to fail validation
        $validatorMock = Mockery::mock('Illuminate\Validation\Validator');
        $validatorMock->shouldReceive('validate')->andThrow($validationException);

        Validator::shouldReceive('make')
            ->once()
            ->withAnyArgs()
            ->andReturn($validatorMock);

        // Create a mock user
        $user = Mockery::mock(User::class);

        // Create the action
        $action = new PasswordReset();

        // Call the action - should throw ValidationException
        $action->reset($user, ['password' => 'weak']);
    }

    /**
     * Test that the password rules are properly applied.
     */
    public function testPasswordRulesAreApplied(): void
    {
        $validatorMock = Mockery::mock('Illuminate\Validation\Validator');
        $validatorMock->shouldReceive('validate')->andReturn(true);

        Validator::shouldReceive('make')
            ->once()
            ->withArgs(function ($data, $rules) {
                // Verify that the password field is being validated with our rules
                return isset($data['password']) && isset($rules['password']);
            })
            ->andReturn($validatorMock);

        // Mock Hash facade
        Hash::shouldReceive('make')->andReturn('hashed-password');

        // Create a mock user
        $user = Mockery::mock(User::class);
        $user->shouldReceive('forceFill')->andReturnSelf();
        $user->shouldReceive('save')->andReturn(true);

        // Create the action
        $action = new PasswordReset();

        // Call the action
        $action->reset($user, ['password' => 'test-password']);
    }
}
