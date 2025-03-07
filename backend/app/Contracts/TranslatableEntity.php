<?php

namespace App\Contracts;

interface TranslatableEntity
{
    public function getTranslatedMessage(): string;
}
