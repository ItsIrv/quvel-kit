<?php

declare(strict_types=1);

namespace Quvel\Core\Logs;

use Illuminate\Log\LogManager;
use Psr\Log\LoggerInterface;
use Closure;
use Illuminate\Support\Facades\Context;
use Throwable;

/**
 * Contextual logger with configurable enrichment and sanitization.
 */
class ContextualLogger implements LoggerInterface
{
    /**
     * Custom context.
     */
    protected static ?Closure $customContext = null;

    /**
     * The log channel to use.
     */
    protected string $channel;

    /**
     * The log context prefix.
     */
    protected string $contextPrefix;

    public function __construct(
        protected LogManager $logger,
        ?string $channel = null,
        string $contextPrefix = ''
    ) {
        $this->channel = $channel ?? config('quvel-core.logging.default_channel', 'stack');
        $this->contextPrefix = $contextPrefix;
    }

    /**
     * Set a custom context callback.
     */
    public static function setCustomContext(?Closure $callback): void
    {
        static::$customContext = $callback;
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
    protected function enrichContext(array|SanitizedContext $context): array
    {
        if (!config('quvel-core.logging.context_enrichment.enabled', true)) {
            return $context instanceof SanitizedContext ? $context->toArray() : $context;
        }

        if (static::$customContext !== null) {
            return (static::$customContext)($context, $this->contextPrefix);
        }

        if ($context instanceof SanitizedContext) {
            $context = $context->toArray();
        }

        if (class_exists(Context::class) && config('quvel-core.logging.include_trace_id', true)) {
            $contextFacade = app(Context::class);

            if ($contextFacade->has('trace_id')) {
                try {
                    $context['trace_id'] = $contextFacade->get('trace_id');
                } catch (Throwable) {
                    //
                }
            }
        }

        if ($this->contextPrefix !== '' && count($context) > 0) {
            $prefixedContext = [];

            foreach ($context as $key => $value) {
                $prefixedContext["$this->contextPrefix.$key"] = $value;
            }

            return $prefixedContext;
        }

        return $context;
    }
}