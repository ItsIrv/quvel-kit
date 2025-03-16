<?php

namespace App\Traits;

trait TranslatableEnum
{
    /**
     * Get the translated message.
     *
     * @return string
     */
    public function getTranslatedMessage(): string
    {
        $translated = __($this->value);

        assert(is_string($translated));

        return $translated;
    }
}
