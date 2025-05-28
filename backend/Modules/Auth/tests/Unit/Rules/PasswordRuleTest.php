<?php

namespace Modules\Auth\Tests\Unit\Rules;

use Illuminate\Validation\Rules\Password;
use Modules\Auth\Rules\PasswordRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use ReflectionClass;
use Tests\TestCase;

#[CoversClass(PasswordRule::class)]
#[Group('auth-module')]
#[Group('auth-rules')]
class PasswordRuleTest extends TestCase
{
    /**
     * Test that PasswordRule::default() returns a Password instance with min length of 8.
     */
    public function testDefaultReturnsPasswordInstanceWithMinLength8(): void
    {
        // Act
        $passwordRule = PasswordRule::default();

        // Assert
        $this->assertInstanceOf(Password::class, $passwordRule);
        
        // Use reflection to check the min property
        $reflection = new ReflectionClass($passwordRule);
        $minProperty = $reflection->getProperty('min');
        $minProperty->setAccessible(true);
        
        $this->assertEquals(8, $minProperty->getValue($passwordRule));
    }
}
