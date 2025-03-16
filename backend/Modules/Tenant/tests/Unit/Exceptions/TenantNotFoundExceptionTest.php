<?php

namespace Modules\Tenant\Tests\Unit\Exceptions;

use Exception;
use Modules\Tenant\Enums\TenantError;
use Modules\Tenant\Exceptions\TenantNotFoundException;
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
    public function test_exception_message(): void
    {
        $exception = new TenantNotFoundException;

        $this->assertEquals(
            TenantError::NOT_FOUND->value,
            $exception->getMessage(),
        );
    }

    /**
     * Test that the exception message can be set with a custom message.
     */
    public function test_exception_with_custom_message(): void
    {
        $customMessage = 'Custom tenant not found message';
        $exception = new TenantNotFoundException($customMessage);

        $this->assertEquals($customMessage, $exception->getMessage());
    }

    /**
     * Test that the exception allows a custom error code.
     */
    public function test_exception_with_code(): void
    {
        $exception = new TenantNotFoundException('Error', 404);

        $this->assertEquals(404, $exception->getCode());
    }

    /**
     * Test that the exception allows a previous exception.
     */
    public function test_exception_with_previous(): void
    {
        $previous = new Exception('Previous exception');
        $exception = new TenantNotFoundException('Error', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
