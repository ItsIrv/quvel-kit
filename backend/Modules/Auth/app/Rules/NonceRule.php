<?php

namespace Modules\Auth\Rules;

class NonceRule
{
    public const RULES = [
        'string',
        'min:2',
        'max:30',
    ];
}
