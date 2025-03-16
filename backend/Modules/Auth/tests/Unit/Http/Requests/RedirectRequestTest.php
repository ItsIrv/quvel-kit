<?php

namespace Modules\Auth\Tests\Unit\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Modules\Auth\Http\Requests\RedirectRequest;
use Modules\Auth\Rules\NonceRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use ReflectionMethod;
use Tests\TestCase;

#[CoversClass(RedirectRequest::class)]
#[Group('auth-module')]
#[Group('auth-requests')]
class RedirectRequestTest extends TestCase
{
    private RedirectRequest $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->request = new RedirectRequest;
    }

    /**
     * Test that the request passes validation with valid data.
     */
    public function test_redirect_request_passes_with_valid_data(): void
    {
        // Arrange
        $validData = [
            'nonce' => str_repeat('a', 85),
            'provider' => 'google',
        ];

        // Act
        $validator = Validator::make($validData, $this->request->rules());

        // Assert
        $this->assertFalse($validator->fails(), 'Validation should pass with valid data.');
    }

    /**
     * Test that the request fails validation with invalid data.
     */
    #[DataProvider('invalidDataProvider')]
    public function test_redirect_request_fails_with_invalid_data(array $invalidData, array $expectedErrors): void
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
     * Data provider for invalid redirect request cases.
     */
    public static function invalidDataProvider(): array
    {
        return [
            'missing provider' => [['nonce' => str_repeat('a', 85)], ['provider']],
            'both fields missing' => [[], ['provider']],
            'invalid nonce format' => [['nonce' => 'invalid_nonce', 'provider' => 'google'], ['nonce']],
        ];
    }

    /**
     * Test that the request rules include the correct rule objects.
     */
    public function test_redirect_request_uses_correct_rules(): void
    {
        // Arrange & Act
        $rules = $this->request->rules();

        // Assert
        $this->assertEquals(
            NonceRule::RULES,
            $rules['nonce'],
        );

        $this->assertContains('required', $rules['provider']);
    }

    /**
     * Test that `prepareForValidation()` correctly merges route parameters.
     * @throws \ReflectionException
     */
    public function test_prepare_for_validation_merges_route_parameters(): void
    {
        // Arrange: Create a request instance without a provider field
        $request = new RedirectRequest([], [], [], [], [], [
            'REQUEST_METHOD' => 'GET',
        ]);

        // Create a class that simulates the route resolver
        $routeResolver = new class
        {
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
