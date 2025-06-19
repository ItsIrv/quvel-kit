<?php

namespace Modules\Auth\Rules;

class NameRule
{
    /**
     * @var array<string>
     */
    public const array RULES = [
        'string',
        'min:2',
        'max:30',
    ];
}
