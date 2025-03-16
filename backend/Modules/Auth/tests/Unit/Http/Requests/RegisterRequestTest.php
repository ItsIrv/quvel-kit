<?php

namespace Modules\Auth\Tests\Unit\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Modules\Auth\app\Http\Requests\RegisterRequest;
use Modules\Auth\app\Rules\EmailRule;
use Modules\Auth\app\Rules\NameRule;
use Modules\Auth\app\Rules\PasswordRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(RegisterRequest::class)]
#[Group('auth-module')]
#[Group('auth-requests')]
class RegisterRequestTest extends TestCase
{
    private RegisterRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new RegisterRequest;
    }

    /**
     * Test that the request passes validation with valid data.
     */
    public function test_register_request_passes_with_valid_data(): void
    {
        // Arrange
        $validData = [
            'email' => 'test@example.com',
            'password' => 'StrongPass123!',
            'name' => 'Test User',
        ];

        // Act
        $validator = Validator::make($validData, $this->request->rules());

        // Assert
        $this->assertFalse($validator->fails(), 'Validation should pass with valid data.');
    }

    /**
     * Test that the request fails validation with missing or invalid fields.
     */
    #[DataProvider('invalidDataProvider')]
    public function test_register_request_fails_with_invalid_data(array $invalidData, array $expectedErrors): void
    {
        // Act
        $validator = Validator::make($invalidData, $this->request->rules());

        // Assert
        $this->assertTrue($validator->fails(), 'Validation should fail with invalid data.');
        foreach ($expectedErrors as $errorKey) {
            $this->assertArrayHasKey($errorKey, $validator->errors()->toArray());
        }
    }

    /**
     * Data provider for invalid register requests.
     */
    public static function invalidDataProvider(): array
    {
        return [
            'missing email' => [['password' => 'StrongPass123!', 'name' => 'Test User'], ['email']],
            'missing password' => [['email' => 'test@example.com', 'name' => 'Test User'], ['password']],
            'missing name' => [['email' => 'test@example.com', 'password' => 'StrongPass123!'], ['name']],
            'invalid email' => [['email' => 'invalid-email', 'password' => 'StrongPass123!', 'name' => 'Test User'], ['email']],
            'short password' => [['email' => 'test@example.com', 'password' => 'short', 'name' => 'Test User'], ['password']],
        ];
    }

    /**
     * Test that rules use correct rule objects.
     */
    public function test_register_request_uses_correct_rules(): void
    {
        // Arrange & Act
        $rules = $this->request->rules();

        // Assert
        $this->assertContains('required', $rules['email']);
        $this->assertContains('string', $rules['email']);
        $this->assertInstanceOf(EmailRule::class, $rules['email'][2]);

        $this->assertContains('required', $rules['password']);
        $this->assertContains('string', $rules['password']);
        $this->assertInstanceOf(PasswordRule::class, $rules['password'][2]);

        $this->assertContains('required', $rules['name']);

        foreach (NameRule::RULES as $rule) {
            $this->assertContains($rule, $rules['name']);
        }
    }
}
