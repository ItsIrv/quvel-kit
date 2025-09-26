<?php

namespace Modules\Core\Enums;

/**
 * Headers available for the core module.
 */
enum CoreHeader: string
{
    /**
     * Header to set the locale.
     */
    case ACCEPT_LANGUAGE = 'Accept-Language';

    /**
     * Header for distributed tracing ID.
     */
    case TRACE_ID = 'X-Trace-ID';

    /**
     * Header for capacitor.
     */
    case CAPACITOR = 'X-Capacitor';

    /**
     * Set the SSR API key.
     * Used when checking if the request is internal.
     */
    case SSR_KEY = 'X-SSR-Key';
}
