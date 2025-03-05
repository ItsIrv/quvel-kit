<?php

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Config;

class ProviderRule implements ValidationRule
{
    /**
     * Validate the provider exists in config.
     *
     * @param  string  $attribute
     * @param  mixed   $value
     * @param  Closure $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validProviders = Config::get('auth.socialite.providers', []);

        assert(is_array($validProviders));

        if (
            !in_array(
                $value,
                $validProviders,
                true,
            )
        ) {
            $fail(__('auth::status.errors.invalidProvider', ['provider' => $attribute]));
        }
    }

    /**
     * Static method for cleaner rule usage.
     *
     * // TODO: Find a good consistency accross rules.
     *
     * @return array
     */
    public static function RULES(): array
    {
        return [new self()];
    }
}
