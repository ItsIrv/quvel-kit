<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait HasTranslationKeyAsMessage
{
    /**
     * Get the translated message.
     */
    public function getTranslatedMessage(): string
    {
        return __($this->message);
    }
}
