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

        if (! in_array($value, $validProviders, true)) {
            $error = __(
                OAuthStatusEnum::INVALID_PROVIDER->value,
                ['provider' => $attribute],
            );

            assert(is_string($error));

            $fail($error);
        }
    }

    /**
     * Static method for cleaner rule usage.
     *
     * @return array<ProviderRule>
     */
    public static function RULES(): array
    {
        return [new self()];
    }
}
