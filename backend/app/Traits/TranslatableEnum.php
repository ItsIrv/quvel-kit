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
        return __($this->value);
    }
}
