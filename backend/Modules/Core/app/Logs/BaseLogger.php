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
    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Log an alert message.
     */
    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Log a critical message.
     */
    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Log an error message.
     */
    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    /**
     * Log a warning message.
     */
    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Log a notice message.
     */
    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Log an info message.
     */
    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    /**
     * Log a debug message.
     */
    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Log a message with an arbitrary level.
     */
    public function log(mixed $level, string|\Stringable $message, array $context = []): void
    {
        // $context = $this->enrichContext($context);

        $this->logger->channel($this->channel)->log($level, $message, $context);
    }

    /**
     * Enrich the log context with additional information.
     */
    protected function enrichContext(array $context): array
    {
        // Add trace ID if available
        if (Context::has('trace_id')) {
            $context['trace_id'] = Context::get('trace_id');
        }

        // Add context prefix if specified
        if ($this->contextPrefix && !empty($context)) {
            $prefixedContext = [];

            foreach ($context as $key => $value) {
                $prefixedContext["{$this->contextPrefix}.{$key}"] = $value;
            }

            return $prefixedContext;
        }

        return $context;
    }
}
