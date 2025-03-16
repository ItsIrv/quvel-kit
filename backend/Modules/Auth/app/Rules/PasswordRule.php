<?php

namespace Modules\Auth\app\Rules;

use Illuminate\Validation\Rules\Password;

/**
 * Password validation rule.
 */
class PasswordRule extends Password
{
    /**
     * Default password validation rules.
     */
    public static function default(): PasswordRule
    {
        return self::min(8);
        // ->mixedCase()
        // ->letters()
        // ->numbers()
        // ->symbols();
    }
}
