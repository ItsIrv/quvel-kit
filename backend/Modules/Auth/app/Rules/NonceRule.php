<?php

namespace Modules\Auth\Rules;

class NonceRule
{
    /**
     * @var array<string>
     */
    public const array RULES = [
        'string',
        'min:85', // 20 (nonce) + 1 (.) + 64 (HMAC)
        'max:128', // Allow slight buffer
    ];
}
