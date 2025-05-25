<?php

namespace Modules\Core\Traits;

trait TranslatableEnum
{
    /**
     * Get the translated message.
     */
    public function getTranslatedMessage(): string
    {
        $translated = __($this->value);

        assert(is_string($translated));

        return $translated;
    }
}
