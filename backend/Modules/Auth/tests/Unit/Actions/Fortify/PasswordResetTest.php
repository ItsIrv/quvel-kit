<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Mockery;
use Modules\Auth\Actions\Fortify\PasswordReset;
use Modules\Auth\Rules\PasswordRule;
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
        Validator::shouldReceive('make')
            ->once()
            ->andReturnSelf();

        Validator::shouldReceive('validate')
            ->once()
            ->andReturn(true);

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

        // Mock the validator to fail validation
        Validator::shouldReceive('make')
            ->once()
            ->andReturnSelf();

        Validator::shouldReceive('validate')
            ->once()
            ->andThrow(ValidationException::class);

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
        // Mock PasswordRule
        $passwordRule = Mockery::mock('overload:' . PasswordRule::class);
        $passwordRule->shouldReceive('default')
            ->once()
            ->andReturn('password-rule-instance');

        // Mock the validator
        Validator::shouldReceive('make')
            ->once()
            ->with(
                ['password' => 'test-password'],
                ['password' => ['required', 'string', 'password-rule-instance']],
            )
            ->andReturnSelf();

        Validator::shouldReceive('validate')
            ->once()
            ->andReturn(true);

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
