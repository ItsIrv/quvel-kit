<?php

namespace App\Contracts;

interface TranslatableException
{
    public function getTranslatedMessage(): string;
}
