<?php

namespace Modules\Auth\Tests\Unit\Actions\Fortify;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Modules\Auth\Actions\Fortify\PasswordValidationRules;
use Modules\Auth\Actions\Fortify\UpdateUserPassword;
use Modules\Auth\Rules\PasswordRule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(UpdateUserPassword::class)]
#[CoversClass(PasswordValidationRules::class)]
#[Group('auth-module')]
#[Group('auth-actions')]
class UpdateUserPasswordTest extends TestCase
{
    /**
     * The update user password action instance.
     */
    private UpdateUserPassword $action;

    /**
     * The validation factory mock.
     */
    private ValidationFactory $validatorMock;

    /**
     * The hasher mock.
     */
    private Hasher $hasherMock;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for dependencies
        $this->validatorMock = $this->createMock(ValidationFactory::class);
        $this->hasherMock    = $this->createMock(Hasher::class);

        // Create the action with mocked dependencies
        $this->action = new UpdateUserPassword($this->validatorMock, $this->hasherMock);
    }

    /**
     * Test that the password rules are correctly defined.
     */
    public function testPasswordRules(): void
    {
        // Use reflection to access the protected method
        $reflectionMethod = new \ReflectionMethod(UpdateUserPassword::class, 'passwordRules');
        $reflectionMethod->setAccessible(true);

        // Get the password rules
        $rules = $reflectionMethod->invoke($this->action);

        // Assert that the rules contain the expected values
        $this->assertContains('required', $rules);
        $this->assertContains('string', $rules);

        // Check that the PasswordRule is included
        $passwordRuleFound = false;
        foreach ($rules as $rule) {
            if ($rule instanceof PasswordRule) {
                $passwordRuleFound = true;
                break;
            }
        }

        $this->assertTrue($passwordRuleFound, 'Password rule not found in rules array');
    }

    /**
     * Clean up after the test.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
