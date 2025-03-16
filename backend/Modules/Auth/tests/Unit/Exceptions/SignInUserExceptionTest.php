<?php

namespace Modules\Auth\Tests\Unit\Exceptions;

use Exception;
use Modules\Auth\Enums\AuthStatusEnum;
use Modules\Auth\Exceptions\SignInUserException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(SignInUserException::class)]
#[Group('auth-module')]
#[Group('auth-exceptions')]
class SignInUserExceptionTest extends TestCase
{
    /**
     * Test that the exception message is set correctly.
     */
    public function test_exception_message(): void
    {
        $exception = new SignInUserException(AuthStatusEnum::INVALID_CREDENTIALS);

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
        $exception = new SignInUserException(AuthStatusEnum::INVALID_CREDENTIALS);

        $this->assertNull($exception->getPrevious());
    }
}
