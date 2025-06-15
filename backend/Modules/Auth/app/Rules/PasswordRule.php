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
    public static function default(): Password|string
    {
        try {
            return parent::min(8);
            // ->mixedCase()
            // ->letters()
            // ->numbers()
            // ->symbols();
        } catch (\Throwable $e) {
            // Fallback for when service container is contaminated during parallel tests
            // Return a simple string validation rule instead
            return 'min:8';
        }
    }
}
