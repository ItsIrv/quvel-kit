<?php

namespace Modules\Auth\Tests\Unit\Http\Requests;

use Illuminate\Support\Facades\Validator;
use Modules\Auth\Http\Requests\RedeemNonceRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(RedeemNonceRequest::class)]
#[Group('auth-module')]
#[Group('auth-requests')]
class RedeemNonceRequestTest extends TestCase
{
    /**
     * Test that the request passes validation with a valid nonce.
     */
    public function testRequestPassesValidationWithValidNonce(): void
    {
        // Arrange
        $validData = ['nonce' => str_repeat('a', 85)];

        // Act
        $validator = Validator::make($validData, (new RedeemNonceRequest())->rules());

        // Assert
        $this->assertFalse($validator->fails());
    }

    /**
     * Test that the request fails validation when 'nonce' is missing.
     */
    public function testRequestFailsValidationWhenNonceIsMissing(): void
    {
        // Arrange
        $invalidData = [];

        // Act
        $validator = Validator::make($invalidData, (new RedeemNonceRequest())->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('nonce', $validator->errors()->toArray());
    }

    /**
     * Test that the request fails validation when 'nonce' is invalid.
     */
    public function testRequestFailsValidationWithInvalidNonce(): void
    {
        // Arrange
        $invalidData = ['nonce' => ''];

        // Act
        $validator = Validator::make($invalidData, (new RedeemNonceRequest())->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('nonce', $validator->errors()->toArray());
    }
}
