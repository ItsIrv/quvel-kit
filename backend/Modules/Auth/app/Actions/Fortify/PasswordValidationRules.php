<?php

namespace Modules\Auth\Actions\Fortify;

use Modules\Auth\Rules\PasswordRule;

/**
 * Trait for password validation rules.
 *
 * Provides consistent password validation rules across the application.
 */
trait PasswordValidationRules
{
    /**
     * Get the validation rules used to validate passwords.
     *
     * @return array<int, \Illuminate\Validation\Rules\Password|string>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', PasswordRule::default()];
    }
}
