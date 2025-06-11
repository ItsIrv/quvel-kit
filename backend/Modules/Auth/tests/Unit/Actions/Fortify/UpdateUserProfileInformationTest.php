<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use App\Models\User;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Actions\Fortify\UpdateUserProfileInformation;
use Modules\Auth\Rules\NameRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestDox;
use Tests\TestCase;
use Mockery;

#[CoversClass(UpdateUserProfileInformation::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class UpdateUserProfileInformationTest extends TestCase
{
    private UpdateUserProfileInformation $action;
    private ValidationFactory $validationFactory;
    private Validator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validationFactory = Mockery::mock(ValidationFactory::class);
        $this->validator = Mockery::mock(Validator::class);

        $this->action = new UpdateUserProfileInformation($this->validationFactory);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[TestDox('constructs with required dependencies')]
    public function testConstructsWithRequiredDependencies(): void
    {
        $action = new UpdateUserProfileInformation($this->validationFactory);

        $this->assertInstanceOf(UpdateUserProfileInformation::class, $action);
    }

    #[TestDox('successfully updates user profile when email is unchanged')]
    public function testSuccessfullyUpdatesUserProfileWhenEmailIsUnchanged(): void
    {
        $input = [
            'name' => 'John Doe',
            'email' => 'user@example.com',
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('email')
            ->andReturn('user@example.com');

        $this->validationFactory
            ->shouldReceive('make')
            ->once()
            ->with($input, [
                'name' => ['required', ...NameRule::RULES],
            ])
            ->andReturn($this->validator);

        $this->validator
            ->shouldReceive('validateWithBag')
            ->once()
            ->with('updateProfileInformation')
            ->andReturn(null);

        $user->shouldReceive('forceFill')
            ->once()
            ->with([
                'name' => 'John Doe',
                'email' => 'user@example.com',
            ])
            ->andReturnSelf();

        $user->shouldReceive('save')
            ->once()
            ->andReturn(true);

        $this->action->update($user, $input);

        // Assert the test completed without throwing an exception
        $this->assertTrue(true);
    }

    #[TestDox('throws validation exception when validation fails')]
    public function testThrowsValidationExceptionWhenValidationFails(): void
    {
        $input = [
            'name' => '',
            'email' => 'invalid-email',
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('email')
            ->andReturn('user@example.com');

        $this->validationFactory
            ->shouldReceive('make')
            ->once()
            ->andReturn($this->validator);

        // Mock the errors method for ValidationException
        $errorBag = Mockery::mock(\Illuminate\Support\MessageBag::class);
        $errorBag->shouldReceive('all')->andReturn(['The name field is required.']);
        $this->validator->shouldReceive('errors')->andReturn($errorBag);
        $this->validator->shouldReceive('getTranslator')->andReturn(app('translator'));

        $this->validator
            ->shouldReceive('validateWithBag')
            ->once()
            ->with('updateProfileInformation')
            ->andThrow(new ValidationException($this->validator));

        $this->expectException(ValidationException::class);

        $this->action->update($user, $input);

        // Assert the test completed without throwing an exception
        $this->assertTrue(true);
    }

    #[TestDox('validates with correct validation bag name')]
    public function testValidatesWithCorrectValidationBagName(): void
    {
        $input = [
            'name' => 'John Doe',
            'email' => 'user@example.com',
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('email')
            ->andReturn('user@example.com');

        $this->validationFactory
            ->shouldReceive('make')
            ->once()
            ->andReturn($this->validator);

        $this->validator
            ->shouldReceive('validateWithBag')
            ->once()
            ->with('updateProfileInformation')
            ->andReturn(null);

        $user->shouldReceive('forceFill')
            ->andReturnSelf();

        $user->shouldReceive('save')
            ->andReturn(true);

        $this->action->update($user, $input);

        // Assert the test completed without throwing an exception
        $this->assertTrue(true);
    }

    #[TestDox('uses name validation rules')]
    public function testUsesNameValidationRules(): void
    {
        $input = [
            'name' => 'John Doe',
            'email' => 'user@example.com',
        ];

        $user = Mockery::mock(User::class);
        $user->shouldReceive('getAttribute')
            ->with('email')
            ->andReturn('user@example.com');

        $this->validationFactory
            ->shouldReceive('make')
            ->once()
            ->with(
                $input,
                Mockery::on(function ($rules) {
                    return isset($rules['name']) && $rules['name'] === ['required', ...NameRule::RULES];
                })
            )
            ->andReturn($this->validator);

        $this->validator
            ->shouldReceive('validateWithBag')
            ->andReturn(null);

        $user->shouldReceive('forceFill')
            ->andReturnSelf();

        $user->shouldReceive('save')
            ->andReturn(true);

        $this->action->update($user, $input);

        // Assert the test completed without throwing an exception
        $this->assertTrue(true);
    }

    #[TestDox('updateVerifiedUser method resets email verification and sends notification')]
    public function testUpdateVerifiedUserMethodResetsEmailVerificationAndSendsNotification(): void
    {
        $input = [
            'name' => 'John Doe',
            'email' => 'newemail@example.com',
        ];

        // Test the protected method directly
        $reflection = new \ReflectionClass($this->action);
        $method = $reflection->getMethod('updateVerifiedUser');
        $method->setAccessible(true);

        // Create a mock user that implements MustVerifyEmail
        $verifiableUser = Mockery::mock(User::class)->makePartial();

        $verifiableUser
            ->shouldReceive('forceFill')
            ->once()
            ->with([
                'name' => 'John Doe',
                'email' => 'newemail@example.com',
                'email_verified_at' => null,
            ])
            ->andReturnSelf();

        $verifiableUser
            ->shouldReceive('save')
            ->once()
            ->andReturn(true);

        $verifiableUser
            ->shouldReceive('sendEmailVerificationNotification')
            ->once();

        $method->invoke($this->action, $verifiableUser, $input);

        // Assert the test completed without throwing an exception
        $this->assertTrue(true);
    }
}
