<?php

namespace Modules\Auth\Rules;

use Illuminate\Validation\Rules\Email;

/**
 * Email validation rule.
 */
class EmailRule
{
    public static function default(): string
    {
        return 'email';
    }
}
