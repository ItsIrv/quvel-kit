<?php

namespace Modules\Auth\Tests\Unit\Exceptions;

use Modules\Auth\Exceptions\OAuthException;
use Modules\Auth\Enums\OAuthStatusEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(OAuthException::class)]
#[Group('auth-module')]
#[Group('auth-exceptions')]
class OAuthExceptionTest extends TestCase
{
    /**
     * Test that the exception message is set correctly.
     */
    public function testExceptionMessage(): void
    {
        $exception = new OAuthException(OAuthStatusEnum::INVALID_PROVIDER);

        $this->assertEquals(
            OAuthStatusEnum::INVALID_PROVIDER->value,
            $exception->getMessage(),
        );
    }

    /**
     * Test that the exception allows a previous exception.
     */
    public function testExceptionWithPrevious(): void
    {
        $previous  = new \Exception('Previous exception');
        $exception = new OAuthException(OAuthStatusEnum::INVALID_PROVIDER);

        $this->assertNull($exception->getPrevious());
    }
}
