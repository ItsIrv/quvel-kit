<?php

namespace Modules\Auth\Rules;

class NonceRule
{
    public const RULES = [
        'string',
        'min:100',
        'max:255',
    ];
}
