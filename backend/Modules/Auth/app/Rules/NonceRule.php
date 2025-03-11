<?php

namespace Modules\Auth\Rules;

class NonceRule
{
    public const RULES = [
        'string',
        'min:1',
        'max:255',
    ];
}
