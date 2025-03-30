<?php

namespace Modules\Auth\app\Rules;

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
