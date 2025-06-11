<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Actions\Fortify\UpdateUserPassword;
use Modules\Auth\Rules\PasswordRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Mockery;

#[CoversClass(UpdateUserPassword::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class UpdateUserPasswordTest extends TestCase
{
    private UpdateUserPassword $action;
    private ValidationFactory $validationFactory;
    private Hasher $hasher;
    private Validator $validator;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationFactory = Mockery::mock(ValidationFactory::class);
        $this->hasher            = Mockery::mock(Hasher::class);
        $this->validator         = Mockery::mock(Validator::class);
        $this->user              = Mockery::mock(User::class);

        $this->action = new UpdateUserPassword($this->validationFactory, $this->hasher);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[TestDox('constructs with required dependencies')]
    public function testConstructsWithRequiredDependencies(): void
    {
        $action = new UpdateUserPassword($this->validationFactory, $this->hasher);

        $this->assertInstanceOf(UpdateUserPassword::class, $action);
    }

    #[TestDox('successfully updates user password with valid input')]
    public function testSuccessfullyUpdatesUserPasswordWithValidInput(): void
    {
        $input = [
            'current_password'      => 'old-password',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $hashedPassword = 'hashed-new-password';

        $this->validationFactory
            ->shouldReceive('make')
            ->once()
            ->with($input, [
                'current_password' => ['required', 'string', 'current_password:web'],
                'password'         => ['required', 'string', PasswordRule::default()],
            ], [
                'current_password.current_password' => 'The provided password does not match your current password.',
            ])
            ->andReturn($this->validator);

        $this->validator
            ->shouldReceive('validateWithBag')
            ->once()
            ->with('updatePassword')
            ->andReturn(null);

        $this->hasher
            ->shouldReceive('make')
            ->once()
            ->with('new-password')
            ->andReturn($hashedPassword);

        $this->user
            ->shouldReceive('forceFill')
            ->once()
            ->with(['password' => $hashedPassword])
            ->andReturnSelf();

        $this->user
            ->shouldReceive('save')
            ->once()
            ->andReturn(true);

        $this->action->update($this->user, $input);

        // Assert the test completed without throwing an exception
        $this->assertTrue(true);
    }

    #[TestDox('throws validation exception when current password is invalid')]
    public function testThrowsValidationExceptionWhenCurrentPasswordIsInvalid(): void
    {
        $input = [
            'current_password'      => 'wrong-password',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $this->validationFactory
            ->shouldReceive('make')
            ->once()
            ->andReturn($this->validator);

        // Mock the errors method for ValidationException
        $errorBag = Mockery::mock(\Illuminate\Support\MessageBag::class);
        $errorBag->shouldReceive('all')->andReturn(['The provided password does not match your current password.']);
        $this->validator->shouldReceive('errors')->andReturn($errorBag);
        $this->validator->shouldReceive('getTranslator')->andReturn(app('translator'));

        $this->validator
            ->shouldReceive('validateWithBag')
            ->once()
            ->with('updatePassword')
            ->andThrow(new ValidationException($this->validator));

        $this->hasher
            ->shouldNotReceive('make');

        $this->user
            ->shouldNotReceive('forceFill');

        $this->expectException(ValidationException::class);

        $this->action->update($this->user, $input);
    }

    #[TestDox('throws validation exception when new password is invalid')]
    public function testThrowsValidationExceptionWhenNewPasswordIsInvalid(): void
    {
        $input = [
            'current_password'      => 'current-password',
            'password'              => 'weak',
            'password_confirmation' => 'weak',
        ];

        $this->validationFactory
            ->shouldReceive('make')
            ->once()
            ->andReturn($this->validator);

        // Mock the errors method for ValidationException
        $errorBag = Mockery::mock(\Illuminate\Support\MessageBag::class);
        $errorBag->shouldReceive('all')->andReturn(['The password must be at least 8 characters.']);
        $this->validator->shouldReceive('errors')->andReturn($errorBag);
        $this->validator->shouldReceive('getTranslator')->andReturn(app('translator'));

        $this->validator
            ->shouldReceive('validateWithBag')
            ->once()
            ->with('updatePassword')
            ->andThrow(new ValidationException($this->validator));

        $this->hasher
            ->shouldNotReceive('make');

        $this->user
            ->shouldNotReceive('forceFill');

        $this->expectException(ValidationException::class);

        $this->action->update($this->user, $input);
    }

    #[TestDox('throws validation exception when password confirmation does not match')]
    public function testThrowsValidationExceptionWhenPasswordConfirmationDoesNotMatch(): void
    {
        $input = [
            'current_password'      => 'current-password',
            'password'              => 'new-password',
            'password_confirmation' => 'different-password',
        ];

        $this->validationFactory
            ->shouldReceive('make')
            ->once()
            ->andReturn($this->validator);

        // Mock the errors method for ValidationException
        $errorBag = Mockery::mock(\Illuminate\Support\MessageBag::class);
        $errorBag->shouldReceive('all')->andReturn(['The password confirmation does not match.']);
        $this->validator->shouldReceive('errors')->andReturn($errorBag);
        $this->validator->shouldReceive('getTranslator')->andReturn(app('translator'));

        $this->validator
            ->shouldReceive('validateWithBag')
            ->once()
            ->with('updatePassword')
            ->andThrow(new ValidationException($this->validator));

        $this->hasher
            ->shouldNotReceive('make');

        $this->user
            ->shouldNotReceive('forceFill');

        $this->expectException(ValidationException::class);

        $this->action->update($this->user, $input);
    }

    #[TestDox('validates with correct validation bag name')]
    public function testValidatesWithCorrectValidationBagName(): void
    {
        $input = [
            'current_password'      => 'current-password',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $this->validationFactory
            ->shouldReceive('make')
            ->once()
            ->andReturn($this->validator);

        $this->validator
            ->shouldReceive('validateWithBag')
            ->once()
            ->with('updatePassword')
            ->andReturn(null);

        $this->hasher
            ->shouldReceive('make')
            ->andReturn('hashed-password');

        $this->user
            ->shouldReceive('forceFill')
            ->andReturnSelf();

        $this->user
            ->shouldReceive('save')
            ->andReturn(true);

        $this->action->update($this->user, $input);

        // Assert the validation was called with the correct bag name
        $this->assertTrue(true);
    }

    #[TestDox('uses password rules from trait')]
    public function testUsesPasswordRulesFromTrait(): void
    {
        $input = [
            'current_password'      => 'current-password',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $expectedRules = ['required', 'string', PasswordRule::default()];

        $this->validationFactory
            ->shouldReceive('make')
            ->once()
            ->with(
                $input,
                Mockery::on(function ($rules) use ($expectedRules) {
                    return $rules['password'] == $expectedRules;
                }),
                Mockery::any()
            )
            ->andReturn($this->validator);

        $this->validator
            ->shouldReceive('validateWithBag')
            ->once()
            ->andReturn(null);

        $this->hasher
            ->shouldReceive('make')
            ->andReturn('hashed-password');

        $this->user
            ->shouldReceive('forceFill')
            ->andReturnSelf();

        $this->user
            ->shouldReceive('save')
            ->andReturn(true);

        $this->action->update($this->user, $input);

        // Assert the test used the expected password rules
        $this->assertTrue(true);
    }

    #[TestDox('includes custom error message for current password validation')]
    public function testIncludesCustomErrorMessageForCurrentPasswordValidation(): void
    {
        $input = [
            'current_password'      => 'current-password',
            'password'              => 'new-password',
            'password_confirmation' => 'new-password',
        ];

        $expectedMessages = [
            'current_password.current_password' => 'The provided password does not match your current password.',
        ];

        $this->validationFactory
            ->shouldReceive('make')
            ->once()
            ->with(
                Mockery::any(),
                Mockery::any(),
                $expectedMessages
            )
            ->andReturn($this->validator);

        $this->validator
            ->shouldReceive('validateWithBag')
            ->once()
            ->andReturn(null);

        $this->hasher
            ->shouldReceive('make')
            ->andReturn('hashed-password');

        $this->user
            ->shouldReceive('forceFill')
            ->andReturnSelf();

        $this->user
            ->shouldReceive('save')
            ->andReturn(true);

        $this->action->update($this->user, $input);

        // Assert the custom message was included
        $this->assertTrue(true);
    }
}
