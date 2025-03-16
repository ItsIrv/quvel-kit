<?php

namespace Modules\Auth\Rules;

class TokenRule
{
    public const RULES = [
        'string',
        'min:40',
        'max:200',
    ];
}
