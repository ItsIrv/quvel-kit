<?php

namespace Modules\Auth\Tests\Unit\Rules;

use Modules\Auth\Rules\EmailRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(EmailRule::class)]
#[Group('auth-module')]
#[Group('auth-rules')]
class EmailRuleTest extends TestCase
{
    /**
     * Test that EmailRule::default() returns the string 'email'.
     */
    public function testDefaultReturnsEmailString(): void
    {
        // Act
        $rule = EmailRule::default();

        // Assert
        $this->assertEquals('email', $rule);
        $this->assertIsString($rule);
    }
}
