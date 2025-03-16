<?php

namespace Modules\Auth\Tests\Unit\Exceptions;

use Modules\Auth\Exceptions\SignInUserException;
use Modules\Auth\Enums\AuthStatusEnum;
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
    public function testExceptionMessage(): void
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
    public function testExceptionWithPrevious(): void
    {
        $previous  = new \Exception('Previous exception');
        $exception = new SignInUserException(AuthStatusEnum::INVALID_CREDENTIALS);

        $this->assertNull($exception->getPrevious());
    }
}
