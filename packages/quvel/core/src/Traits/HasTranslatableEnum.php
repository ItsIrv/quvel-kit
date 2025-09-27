<?php

declare(strict_types=1);

namespace Quvel\Core\Traits;

/**
 * Trait for enums that need translation support.
 * Provides a simple way to translate enum values using the Laravel translation system.
 *
 * @property-read string $value The enum value
 */
trait HasTranslatableEnum
{
    /**
     * Get the translated message for this enum value.
     */
    public function getTranslatedMessage(?string $context = null): string
    {
        $key = $context ? "$context.$this->value" : $this->value;

        if (function_exists('__')) {
            $translation = __($key);
            return is_string($translation) ? $translation : $this->value;
        }

        return $this->value;
    }

    /**
     * Get the translation key for this enum value.
     */
    public function getTranslationKey(?string $context = null): string
    {
        return $context ? "$context.$this->value" : $this->value;
    }
}