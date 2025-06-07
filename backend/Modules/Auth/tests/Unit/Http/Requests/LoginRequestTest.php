<?php

namespace Modules\Auth\Tests\Unit\Http\Requests;

use Modules\Auth\Http\Requests\LoginRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(LoginRequest::class)]
#[Group('auth-module')]
#[Group('auth-requests')]
class LoginRequestTest extends TestCase
{
    /**
     * Test that the request defines the correct validation rules.
     */
    public function testRequestDefinesCorrectValidationRules(): void
    {
        // Arrange - use reflection to access rules method without executing the class
        $reflection = new \ReflectionClass(LoginRequest::class);
        $method = $reflection->getMethod('rules');
        
        // Create instance but don't initialize Laravel-specific parts
        $request = new class extends LoginRequest {
            public function __construct() {
                // Don't call parent constructor to avoid Laravel dependencies
            }
        };

        // Act
        $rules = $method->invoke($request);

        // Assert
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        
        // Check email rules
        $this->assertIsArray($rules['email']);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('string', $rules['email']);
        
        // Check password rules
        $this->assertIsArray($rules['password']);
        $this->assertContains('required', $rules['password']);
        $this->assertContains('string', $rules['password']);
    }
}