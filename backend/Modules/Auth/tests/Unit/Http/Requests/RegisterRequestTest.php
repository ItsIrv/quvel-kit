<?php

namespace Modules\Auth\Tests\Unit\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Rules\NameRule;
use Modules\Auth\Rules\PasswordRule;
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
        $this->request = new RegisterRequest();
    }

    /**
     * Test that the request passes validation with valid data.
     */
    public function testRegisterRequestPassesWithValidData(): void
    {
        // Arrange
        $validData = [
            'email'    => 'test@example.com',
            'password' => 'StrongPass123!',
            'name'     => 'Test User',
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
    public function testRegisterRequestFailsWithInvalidData(array $invalidData, array $expectedErrors): void
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
            'missing email'    => [['password' => 'StrongPass123!', 'name' => 'Test User'], ['email']],
            'missing password' => [['email' => 'test@example.com', 'name' => 'Test User'], ['password']],
            'missing name'     => [['email' => 'test@example.com', 'password' => 'StrongPass123!'], ['name']],
            'invalid email'    => [['email' => 'invalid-email', 'password' => 'StrongPass123!', 'name' => 'Test User'], ['email']],
            'short password'   => [['email' => 'test@example.com', 'password' => 'short', 'name' => 'Test User'], ['password']],
        ];
    }

    /**
     * Test that rules use correct rule objects.
     */
    public function testRegisterRequestUsesCorrectRules(): void
    {
        // Arrange & Act
        $rules = $this->request->rules();

        // Assert
        $this->assertContains('required', $rules['email']);
        $this->assertContains('string', $rules['email']);
        $this->assertEquals('email', $rules['email'][2]);

        $this->assertContains('required', $rules['password']);
        $this->assertContains('string', $rules['password']);
        $this->assertInstanceOf(PasswordRule::class, $rules['password'][2]);

        $this->assertContains('required', $rules['name']);

        foreach (NameRule::RULES as $rule) {
            $this->assertContains($rule, $rules['name']);
        }
    }
}
