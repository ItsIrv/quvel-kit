<?php

namespace Modules\Tenant\Exceptions;

use RuntimeException;

/**
 * TenantIdResolutionException
 *
 * Exception thrown when a tenant ID cannot be resolved for a notification
 * or other tenant-aware component.
 */
class TenantIdResolutionException extends RuntimeException
{
    /**
     * Create a new tenant ID resolution exception
     *
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct(
        string $message = 'Unable to determine tenant ID',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Create a new exception for notification tenant ID resolution
     *
     * @return static
     */
    public static function forNotification(): static
    {
        return new static('Unable to determine tenant ID for notification');
    }

    /**
     * Create a new exception for broadcast channel tenant ID resolution
     *
     * @return static
     */
    public static function forBroadcastChannel(): static
    {
        return new static('Unable to determine tenant ID for broadcast channel');
    }

    /**
     * Create a new exception for a specific context
     *
     * @param string $context
     * @return static
     */
    public static function forContext(string $context): static
    {
        return new static("Unable to determine tenant ID for {$context}");
    }
}
