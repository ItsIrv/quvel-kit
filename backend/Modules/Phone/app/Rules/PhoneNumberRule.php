<?php

namespace Modules\Phone\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for phone numbers.
 */
class PhoneNumberRule implements ValidationRule
{
    public function __construct(
        private readonly ?string $country = null,
    ) {
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || empty($value)) {
            $fail('The :attribute must be a valid phone number.');
            return;
        }

        $country    = $this->country ?? config('phone.phone.default_country', 'US');
        $cleanPhone = preg_replace('/\D/', '', $value);

        if (!$cleanPhone) {
            $fail('The :attribute must be a valid phone number.');
            return;
        }

        $formats = config('phone.phone.formats', []);

        if (!isset($formats[$country])) {
            $fail("Phone validation not configured for {$country}.");
            return;
        }

        $format = $formats[$country];

        if (strlen($cleanPhone) < $format['min_length'] || strlen($cleanPhone) > $format['max_length']) {
            $fail("The :attribute must be between {$format['min_length']} and {$format['max_length']} digits for {$country}.");

            return;
        }

        if (isset($format['pattern']) && !preg_match($format['pattern'], $cleanPhone)) {
            $fail("The :attribute must be a valid phone number for {$country}.");
        }
    }
}
