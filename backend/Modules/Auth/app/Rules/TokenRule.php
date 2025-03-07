<?php

namespace Modules\Auth\Rules;

class TokenRule
{
    public const RULES = [
        'string',
        'min:30',
        'max:64',
    ];
}
