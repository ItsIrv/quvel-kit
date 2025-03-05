<?php

namespace Modules\Auth\Rules;

class TokenRule
{
    public const RULES = [
        'string',
        'size:64',
    ];
}
