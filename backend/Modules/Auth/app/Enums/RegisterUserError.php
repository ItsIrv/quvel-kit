<?php

namespace Modules\Auth\Enums;

enum RegisterUserError: string
{
    case EMAIL_ALREADY_IN_USE = 'auth.errors.emailAlreadyInUse';
}
