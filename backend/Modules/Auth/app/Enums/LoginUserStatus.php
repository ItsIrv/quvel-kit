<?php

namespace Modules\Auth\Enums;

enum LoginUserStatus: string
{
    case USER_NOT_FOUND      = 'auth.errors.userNotFound';
    case INVALID_CREDENTIALS = 'auth.errors.invalidCredentials';
    case EMAIL_NOT_VERIFIED  = 'auth.warnings.emailNotVerified';
    case SUCCESS             = 'auth.success.loggedIn';
}
