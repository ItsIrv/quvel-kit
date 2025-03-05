<?php

namespace Modules\Tenant\Tests\Unit\Exceptions;

use Modules\Tenant\Exceptions\TenantNotFoundException;
use Modules\Tenant\Enums\TenantError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[CoversClass(TenantNotFoundException::class)]
#[Group('tenant-module')]
#[Group('tenant-exceptions')]
class TenantNotFoundExceptionTest extends TestCase
{
    /**
     * Test that the default exception message is set correctly.
     */
    public function testExceptionMessage(): void
    {
        $exception = new TenantNotFoundException();

        $this->assertEquals(
            TenantError::NOT_FOUND->value,
            $exception->getMessage(),
        );
    }

    /**
     * Test that the exception message can be set with a custom message.
     */
    public function testExceptionWithCustomMessage(): void
    {
        $customMessage = 'Custom tenant not found message';
        $exception     = new TenantNotFoundException($customMessage);

        $this->assertEquals($customMessage, $exception->getMessage());
    }

    /**
     * Test that the exception allows a custom error code.
     */
    public function testExceptionWithCode(): void
    {
        $exception = new TenantNotFoundException('Error', 404);

        $this->assertEquals(404, $exception->getCode());
    }

    /**
     * Test that the exception allows a previous exception.
     */
    public function testExceptionWithPrevious(): void
    {
        $previous  = new \Exception('Previous exception');
        $exception = new TenantNotFoundException('Error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
