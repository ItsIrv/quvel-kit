<?php

namespace App\Traits;

trait TranslatableException
{
    /**
     * Get the translated message.
     */
    public function getTranslatedMessage(): string
    {
        $translation = __($this->message);

        return is_string($translation) ? $translation : '';
    }
}
