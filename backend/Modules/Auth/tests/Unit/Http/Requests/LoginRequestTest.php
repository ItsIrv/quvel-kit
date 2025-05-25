<?php

namespace Modules\Auth\Tests\Unit\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Modules\Auth\Http\Requests\LoginRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(LoginRequest::class)]
#[Group('auth-module')]
#[Group('auth-requests')]
class LoginRequestTest extends TestCase
{
    /**
     * Test that the request passes validation with correct data.
     */
    public function testRequestPassesValidationWithValidData(): void
    {
        // Arrange
        $validData = [
            'email' => 'test@example.com',
            'password' => 'SecurePassword123!',
        ];

        // Act
        $validator = Validator::make($validData, (new LoginRequest())->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /**
     * Test that the request fails validation when 'email' is missing.
     */
    public function testRequestFailsValidationWhenEmailIsMissing(): void
    {
        // Arrange
        $invalidData = [
            'password' => 'SecurePassword123!',
        ];

        // Act
        $validator = Validator::make($invalidData, (new LoginRequest())->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /**
     * Test that the request fails validation when 'password' is missing.
     */
    public function testRequestFailsValidationWhenPasswordIsMissing(): void
    {
        // Arrange
        $invalidData = [
            'email' => 'test@example.com',
        ];

        // Act
        $validator = Validator::make($invalidData, (new LoginRequest())->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }

    /**
     * Test that 'email' follows EmailRule validation.
     */
    public function testRequestFailsValidationWithInvalidEmail(): void
    {
        // Arrange
        $invalidData = [
            'email' => 'invalid-email',
            'password' => 'SecurePassword123!',
        ];

        // Act
        $validator = Validator::make($invalidData, (new LoginRequest())->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    /**
     * Test that 'password' follows PasswordRule validation.
     */
    public function testRequestFailsValidationWithInvalidPassword(): void
    {
        // Arrange
        $invalidData = [
            'email' => 'test@example.com',
            'password' => '123', // Too short, assuming PasswordRule requires stronger passwords
        ];

        // Act
        $validator = Validator::make($invalidData, (new LoginRequest())->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('password', $validator->errors()->toArray());
    }
}
