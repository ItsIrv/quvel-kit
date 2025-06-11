<?php

namespace Modules\Auth\Tests\Unit\Rules;

use Illuminate\Validation\Rules\Password;
use Modules\Auth\Rules\PasswordRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(PasswordRule::class)]
#[Group('auth-module')]
#[Group('auth-rules')]
class PasswordRuleTest extends TestCase
{
    /**
     * Test that the default method returns a Password rule with minimum 8 characters.
     */
    public function testDefaultReturnsPasswordRuleWithMinimum8Characters(): void
    {
        $rule = PasswordRule::default();

        $this->assertInstanceOf(Password::class, $rule);
        
        // Test with a password shorter than 8 characters (should fail)
        $validator = validator(['password' => 'short'], ['password' => $rule]);
        $this->assertTrue($validator->fails());
        $this->assertStringContainsString('8', $validator->errors()->first('password'));

        // Test with a password of exactly 8 characters (should pass)
        $validator = validator(['password' => '12345678'], ['password' => $rule]);
        $this->assertFalse($validator->fails());

        // Test with a password longer than 8 characters (should pass)
        $validator = validator(['password' => 'longerpassword'], ['password' => $rule]);
        $this->assertFalse($validator->fails());
    }

    /**
     * Test that the rule extends Laravel's Password rule.
     */
    public function testExtendsLaravelPasswordRule(): void
    {
        $rule = PasswordRule::default();
        $this->assertInstanceOf(Password::class, $rule);
    }

    /**
     * Test that the default rule can be chained with additional constraints.
     */
    public function testDefaultRuleCanBeChainedWithAdditionalConstraints(): void
    {
        $rule = PasswordRule::default()->letters()->numbers();

        // Test with only numbers (should fail - needs letters)
        $validator = validator(['password' => '12345678'], ['password' => $rule]);
        $this->assertTrue($validator->fails());

        // Test with only letters (should fail - needs numbers)
        $validator = validator(['password' => 'abcdefgh'], ['password' => $rule]);
        $this->assertTrue($validator->fails());

        // Test with both letters and numbers (should pass)
        $validator = validator(['password' => 'abc12345'], ['password' => $rule]);
        $this->assertFalse($validator->fails());
    }

    /**
     * Test edge cases for minimum length validation.
     */
    public function testEdgeCasesForMinimumLengthValidation(): void
    {
        $rule = PasswordRule::default();

        // Test with exactly 7 characters (should fail)
        $validator = validator(['password' => '1234567'], ['password' => $rule]);
        $this->assertTrue($validator->fails());

        // Test with exactly 8 characters (should pass)
        $validator = validator(['password' => '12345678'], ['password' => $rule]);
        $this->assertFalse($validator->fails());

        // Test with 9 characters (should pass)
        $validator = validator(['password' => '123456789'], ['password' => $rule]);
        $this->assertFalse($validator->fails());
    }

    /**
     * Test that the rule works with special characters and unicode.
     */
    public function testRuleWorksWithSpecialCharactersAndUnicode(): void
    {
        $rule = PasswordRule::default();

        // Test with special characters
        $validator = validator(['password' => '!@#$%^&*'], ['password' => $rule]);
        $this->assertFalse($validator->fails());

        // Test with unicode characters
        $validator = validator(['password' => 'páss wørd'], ['password' => $rule]);
        $this->assertFalse($validator->fails());

        // Test with emojis (should count as characters)
        $validator = validator(['password' => '🔒🔑🛡️🗝️🔐🔓🔒🔑'], ['password' => $rule]);
        $this->assertFalse($validator->fails());
    }
}