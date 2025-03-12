<?php

namespace Modules\Auth\Rules;

class TokenRule
{
    public const RULES = [
        'string',
        'min:160',
        'max:200',
    ];
}
