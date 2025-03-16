<?php

namespace Modules\Auth\Tests\Unit\Rules;

use Illuminate\Support\Facades\Validator;
use Modules\Auth\app\Rules\PasswordRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(PasswordRule::class)]
#[Group('auth-module')]
#[Group('auth-rules')]
class PasswordRuleTest extends TestCase
{
    /**
     * Test that the PasswordRule enforces minimum length correctly.
     */
    #[DataProvider('passwordProvider')]
    public function test_password_rule_enforces_minimum_length(string $password, bool $shouldPass): void
    {
        // Arrange
        $validator = Validator::make(
            ['password' => $password],
            ['password' => PasswordRule::default()],
        );

        // Act
        $passes = ! $validator->fails();

        // Assert
        $this->assertEquals($shouldPass, $passes, "Failed asserting that '$password' validation is correct.");
    }

    /**
     * Provides test cases for password validation.
     */
    public static function passwordProvider(): array
    {
        return [
            'valid password (8 chars)' => ['password1', true],
            'valid password (longer)' => ['supersecure123!', true],
            'invalid password (too short)' => ['short', false],
        ];
    }
}
