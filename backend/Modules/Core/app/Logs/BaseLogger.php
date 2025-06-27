<?php

namespace Modules\Core\Logs;

use Illuminate\Log\LogManager;
use Illuminate\Support\Facades\Context;
use Psr\Log\LoggerInterface;

/**
 * Base logger implementation.
 * Provides common functionality for all entity-specific loggers.
 * Implements PSR-3 LoggerInterface for standardized logging.
 */
abstract class BaseLogger implements LoggerInterface
{
    /**
     * The log channel to use.
     */
    protected string $channel = 'stack';

    /**
     * The log context prefix.
     */
    protected string $contextPrefix = '';

    /**
     * Create a new logger instance.
     *
     * @param \Illuminate\Log\LogManager $logger
     */
    public function __construct(protected LogManager $logger)
    {
    }

    /**
     * Log an emergency message.
     */
    public function emergency($message, array|SanitizedContext $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Log an alert message.
     */
    public function alert($message, array|SanitizedContext $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Log a critical message.
     */
    public function critical($message, array|SanitizedContext $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Log an error message.
     */
    public function error($message, array|SanitizedContext $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log a warning message.
     */
    public function warning($message, array|SanitizedContext $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log a notice message.
     */
    public function notice($message, array|SanitizedContext $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Log an info message.
     */
    public function info($message, array|SanitizedContext $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log a debug message.
     */
    public function debug($message, array|SanitizedContext $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log a message with an arbitrary level.
     */
    public function log(mixed $level, $message, array|SanitizedContext $context = []): void
    {
        $context = $this->enrichContext($context);

        $this->logger->channel($this->channel)->log($level, $message, $context);
    }

    /**
     * Enrich the log context with additional information.
     */
    /**
     * @param array<string, mixed>|SanitizedContext $context
     * @return array<string, mixed>
     */
    protected function enrichContext(array|SanitizedContext $context): array
    {
        // Handle SanitizedContext by converting to sanitized array
        if ($context instanceof SanitizedContext) {
            $context = $context->toArray();
        }

        // Add trace ID if available
        if (Context::has('trace_id')) {
            $context['trace_id'] = Context::get('trace_id');
        }

        // Add context prefix if specified
        if ($this->contextPrefix !== '' && count($context) > 0) {
            $prefixedContext = [];

            foreach ($context as $key => $value) {
                $prefixedContext["{$this->contextPrefix}.{$key}"] = $value;
            }

            return $prefixedContext;
        }

        return $context;
    }
}
