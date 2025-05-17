<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use Mockery;
use Modules\Auth\Actions\Fortify\PasswordValidationRules;
use Modules\Auth\Rules\PasswordRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(PasswordValidationRules::class)]
#[Group('auth-module')]
#[Group('auth-fortify')]
class PasswordValidationRulesTest extends TestCase
{
    public function testPasswordRulesReturnsCorrectRules(): void
    {
        // Create a mock PasswordRule
        $passwordRuleMock = Mockery::mock('overload:' . PasswordRule::class);
        $passwordRuleMock->shouldReceive('default')
            ->once()
            ->andReturn('password-rule-instance');

        // Create a test class that uses the trait
        $testClass = new class
        {
            use PasswordValidationRules;

            public function getPasswordRules(): array
            {
                return $this->passwordRules();
            }
        };

        // Get the rules
        $rules = $testClass->getPasswordRules();

        // Assert the rules are correct
        $this->assertIsArray($rules);
        $this->assertCount(3, $rules);
        $this->assertEquals('required', $rules[0]);
        $this->assertEquals('string', $rules[1]);
        $this->assertEquals('password-rule-instance', $rules[2]);
    }
}
