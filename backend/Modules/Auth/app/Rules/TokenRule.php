<?php

namespace Modules\Auth\Rules;

class TokenRule
{
    /**
     * @var array<string>
     */
    public const array RULES = [
        'string',
        'min:40',
        'max:200',
    ];
}
