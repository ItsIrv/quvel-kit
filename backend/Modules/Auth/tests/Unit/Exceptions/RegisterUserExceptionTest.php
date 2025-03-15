<?php

namespace Modules\Auth\Tests\Unit\Exceptions;

use Modules\Auth\Exceptions\RegisterUserException;
use Modules\Auth\Enums\AuthStatusEnum;
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
    public function testExceptionMessage(): void
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
    public function testExceptionWithPrevious(): void
    {
        $previous  = new \Exception('Previous exception');
        $exception = new RegisterUserException(AuthStatusEnum::EMAIL_ALREADY_IN_USE);

        $this->assertNull($exception->getPrevious());
    }
}
