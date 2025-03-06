<?php

namespace Modules\Tenant\Exceptions;

use Exception;
use Modules\Tenant\Enums\TenantError;

/**
 * Exception to be thrown when the tenant was not found.
 */
class TenantNotFoundException extends Exception
{
    /**
     * Create a new exception instance.
     * @param string $message The error message.
     * @param int $code The error code.
     * @param Exception|null $previous The previous exception.
     */
    public function __construct(
        $message = TenantError::NOT_FOUND->value,
        $code = 0,
        Exception|null $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
