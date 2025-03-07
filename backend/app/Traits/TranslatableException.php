<?php

namespace App\Traits;

use App\Contracts\TranslatableEntity;

trait TranslatableException
{
    /**
     * Get the translated message.
     */
    public function getTranslatedMessage(): string
    {
        $translation = $this->message instanceof TranslatableEntity
            ? $this->message->getTranslatedMessage()
            : __($this->message);

        assert(is_string($translation));

        return $translation;
    }
}
