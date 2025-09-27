<?php

declare(strict_types=1);

namespace Quvel\Core\Enums;

/**
 * HTTP headers used throughout the framework.
 * Header values can be configured via the core config.
 */
enum HttpHeader: string
{
    /**
     * Standard Accept-Language header for locale negotiation.
     */
    case ACCEPT_LANGUAGE = 'Accept-Language';

    /**
     * Custom header for distributed tracing ID.
     */
    case TRACE_ID = 'X-Trace-ID';

    /**
     * Custom header for platform detection (web, capacitor, electron, etc.).
     */
    case PLATFORM = 'X-Platform';

    /**
     * Custom header for server-side rendering API key.
     * Used for internal request authentication.
     */
    case SSR_KEY = 'X-SSR-Key';

    /**
     * Get the configured header value or use the default.
     */
    public function getValue(): string
    {
        $configKey = match ($this) {
            self::TRACE_ID => 'quvel-core.headers.trace_id',
            self::PLATFORM => 'quvel-core.headers.platform',
            self::SSR_KEY => 'quvel-core.headers.ssr_key',
            default => null,
        };

        if ($configKey && function_exists('config')) {
            return config($configKey, $this->value);
        }

        return $this->value;
    }
}