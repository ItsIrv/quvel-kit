<?php

namespace Modules\Auth\Tests\Unit\Exceptions;

use Exception;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\RegisterUserException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(RegisterUserException::class)]
#[Group('auth-module')]
#[Group('auth-exceptions')]
class RegisterUserExceptionTest extends TestCase
{
    /**
     * Test that the exception message is set correctly.
     */
    public function test_exception_message(): void
    {
        $exception = new RegisterUserException(AuthStatusEnum::EMAIL_ALREADY_IN_USE);

        $this->assertEquals(
            AuthStatusEnum::EMAIL_ALREADY_IN_USE->value,
            $exception->getMessage(),
        );
    }

    /**
     * Test that the exception allows a previous exception.
     */
    public function test_exception_with_previous(): void
    {
        $previous = new Exception('Previous exception');
        $exception = new RegisterUserException(AuthStatusEnum::EMAIL_ALREADY_IN_USE);

        $this->assertNull($exception->getPrevious());
    }
}
