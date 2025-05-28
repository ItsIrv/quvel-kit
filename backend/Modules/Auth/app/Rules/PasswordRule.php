<?php

namespace Modules\Auth\Rules;

use Illuminate\Validation\Rules\Password;

/**
 * Password validation rule.
 */
class PasswordRule extends Password
{
    /**
     * Default password validation rules.
     */
    public static function default(): Password
    {
        return parent::min(8);
        // ->mixedCase()
        // ->letters()
        // ->numbers()
        // ->symbols();
    }
}
