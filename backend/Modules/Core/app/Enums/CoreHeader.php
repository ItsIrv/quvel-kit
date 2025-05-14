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
}
