<?php

namespace Modules\Auth\Rules;

class NonceRule
{
    public const RULES = [
        'string',
        'min:85', // 20 (nonce) + 1 (.) + 64 (HMAC)
        'max:128', // Allow slight buffer
    ];
}
