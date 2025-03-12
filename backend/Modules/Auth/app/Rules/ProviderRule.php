<?php

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Config;
use Modules\Auth\Enums\OAuthStatusEnum;

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
        $validProviders = Config::get('auth.oauth.providers', []);

        assert(is_array($validProviders));

        if (
            !in_array(
                $value,
                $validProviders,
                true,
            )
        ) {
            $fail(__(
                OAuthStatusEnum::INVALID_PROVIDER->value,
                ['provider' => $attribute],
            ));
        }
    }

    /**
     * Static method for cleaner rule usage.
     *
     * // TODO: Find a good consistency accross rules.
     *
     * @return array<int, self>
     */
    public static function RULES(): array
    {
        return [new self()];
    }
}
