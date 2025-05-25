<?php

namespace Modules\Auth\Rules;

use Illuminate\Validation\Rules\Email;

/**
 * Email validation rule.
 */
class EmailRule extends Email
{
    // public static function default(): EmailRule
    // {
    //     return self::strict()
    //         ->validateMxRecord()
    //         ->preventSpoofing();
    // }
}
