<?php

namespace Modules\Tenant\app\Exceptions;

use Exception;
use Modules\Tenant\Enums\TenantError;
use Throwable;

/**
 * Exception to be thrown when the tenant was not found.
 */
class TenantMismatchException extends Exception
{
    /**
     * Create a new exception instance.
     * @param string $message The error message.
     * @param int $code The error code.
     * @param Throwable|null $previous The previous exception.
     */
    public function __construct(
        $message = TenantError::TENANT_MISMATCH->value,
        $code = 0,
        Throwable|null $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
