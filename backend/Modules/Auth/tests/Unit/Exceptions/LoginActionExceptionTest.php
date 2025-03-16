<?php

namespace Modules\Auth\Tests\Unit\Exceptions;

use Exception;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\LoginActionException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(LoginActionException::class)]
#[Group('auth-module')]
#[Group('auth-exceptions')]
class LoginActionExceptionTest extends TestCase
{
    /**
     * Test that the exception message is set correctly.
     */
    public function test_exception_message(): void
    {
        $exception = new LoginActionException(AuthStatusEnum::INVALID_CREDENTIALS);

        $this->assertEquals(
            AuthStatusEnum::INVALID_CREDENTIALS->value,
            $exception->getMessage(),
        );
    }

    /**
     * Test that the exception allows a previous exception.
     */
    public function test_exception_with_previous(): void
    {
        $previous = new Exception('Previous exception');
        $exception = new LoginActionException(AuthStatusEnum::INVALID_CREDENTIALS);

        $this->assertNull($exception->getPrevious());
    }
}
