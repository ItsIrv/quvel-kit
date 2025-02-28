<?php

namespace Modules\Auth\app\Rules;

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
