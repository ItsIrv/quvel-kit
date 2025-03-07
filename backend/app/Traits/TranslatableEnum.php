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
        assert(is_string(__($this->value)));

        return __($this->value);
    }
}
