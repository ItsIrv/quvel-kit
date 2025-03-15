<?php

namespace Modules\Auth\Rules;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Validation\ValidationRule;
use Modules\Auth\Enums\OAuthStatusEnum;

class ProviderRule implements ValidationRule
{
    /**
     * Validate the provider exists in config.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validProviders = app(ConfigRepository::class)->get('auth.oauth.providers', []);

        assert(is_array($validProviders));

        if (!in_array($value, $validProviders, true)) {
            $fail(__(
                OAuthStatusEnum::INVALID_PROVIDER->value,
                ['provider' => $attribute],
            ));
        }
    }

    /**
     * Static method for cleaner rule usage.
     */
    public static function RULES(): array
    {
        return [new self()];
    }
}
