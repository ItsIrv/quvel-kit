<?php

namespace Modules\Auth\Tests\Unit\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Modules\Auth\Http\Requests\RedirectRequest;
use Modules\Auth\Rules\NonceRule;
use Modules\Auth\Rules\ProviderRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(RedirectRequest::class)]
#[Group('auth-module')]
#[Group('auth-requests')]
class RedirectRequestTest extends TestCase
{
    private RedirectRequest $request;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = new RedirectRequest();
    }

    /**
     * Test that the request passes validation with valid data.
     */
    public function testRedirectRequestPassesWithValidData(): void
    {
        // Arrange
        $validData = [
            'nonce'    => str_repeat('a', 85),
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
    public function testRedirectRequestFailsWithInvalidData(array $invalidData, array $expectedErrors): void
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
            'missing provider'     => [['nonce' => str_repeat('a', 85)], ['provider']],
            'both fields missing'  => [[], ['provider']],
            'invalid nonce format' => [['nonce' => 'invalid_nonce', 'provider' => 'google'], ['nonce']],
        ];
    }

    /**
     * Test that the request rules include the correct rule objects.
     */
    public function testRedirectRequestUsesCorrectRules(): void
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
}
