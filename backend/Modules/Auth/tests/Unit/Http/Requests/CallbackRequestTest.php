<?php

namespace Modules\Auth\Tests\Unit\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Modules\Auth\Http\Requests\CallbackRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use Tests\TestCase;

#[CoversClass(CallbackRequest::class)]
#[Group('auth-module')]
#[Group('auth-requests')]
class CallbackRequestTest extends TestCase
{
    /**
     * Test that the request passes validation with correct data.
     */
    public function testRequestPassesValidationWithValidData(): void
    {
        // Arrange
        $validData = [
            'state' => bin2hex(random_bytes(32)),
            'provider' => 'google',
        ];

        // Act
        $validator = Validator::make($validData, (new CallbackRequest())->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /**
     * Test that the request fails validation when 'state' is missing.
     */
    public function testRequestFailsValidationWhenStateIsMissing(): void
    {
        // Arrange
        $invalidData = [
            'provider' => 'google',
        ];

        // Act
        $validator = Validator::make($invalidData, (new CallbackRequest())->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('state', $validator->errors()->toArray());
    }

    /**
     * Test that the request fails validation when 'provider' is missing.
     */
    public function testRequestFailsValidationWhenProviderIsMissing(): void
    {
        // Arrange
        $invalidData = [
            'state' => bin2hex(random_bytes(32)),
        ];

        // Act
        $validator = Validator::make($invalidData, (new CallbackRequest())->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('provider', $validator->errors()->toArray());
    }

    /**
     * Test that 'provider' follows ProviderRule validation.
     */
    public function testRequestFailsValidationWithInvalidProvider(): void
    {
        // Arrange
        $invalidData = [
            'state' => bin2hex(random_bytes(32)),
            'provider' => 'invalid-provider',
        ];

        // Act
        $validator = Validator::make($invalidData, (new CallbackRequest())->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('provider', $validator->errors()->toArray());
    }

    /**
     * Test that 'state' follows TokenRule validation.
     */
    public function testRequestFailsValidationWithInvalidState(): void
    {
        // Arrange
        $invalidData = [
            'state' => 'invalid-token',
            'provider' => 'google',
        ];

        // Act
        $validator = Validator::make($invalidData, (new CallbackRequest())->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('state', $validator->errors()->toArray());
    }

    /**
     * Test that prepareForValidation correctly merges route parameters.
     *
     * @throws \ReflectionException
     */
    public function testPrepareForValidationMergesRouteParameters(): void
    {
        // Arrange: Create a request instance without a provider field
        $request = new CallbackRequest([], [], [], [], [], [
            'REQUEST_METHOD' => 'GET',
        ]);

        // Create a class that simulates the route resolver
        $routeResolver = new class () {
            /**
             * Simulates retrieving a route parameter.
             */
            public function parameter(string $key): ?string
            {
                return $key === 'provider' ? 'google' : null;
            }
        };

        // Assign the route resolver to the request
        $request->setRouteResolver(function () use ($routeResolver) {
            return $routeResolver;
        });

        // Use reflection to call the protected prepareForValidation method
        $reflection = new ReflectionMethod($request, 'prepareForValidation');
        $reflection->invoke($request);

        // Assert: The 'provider' field should now be set in the request data
        $this->assertEquals('google', $request->input('provider'));
    }
}
