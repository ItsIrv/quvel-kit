<?php

namespace Modules\Core\Contracts;

interface TranslatableEntity
{
    public function getTranslatedMessage(): string;
}
