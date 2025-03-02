<?php

namespace Modules\Auth\app\Rules;

class NameRule
{
    public const RULES = [
        'string',
        'min:2',
        'max:30',
    ];
}
