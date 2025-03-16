<?php

namespace Modules\Auth\Enums;

use App\Traits\TranslatableEnum;

enum AuthStatusEnum: string
{
    use TranslatableEnum;

    case USER_NOT_FOUND = 'auth::status.errors.userNotFound';
    case INVALID_CREDENTIALS = 'auth::status.errors.invalidCredentials';
    case EMAIL_ALREADY_IN_USE = 'auth::status.errors.emailAlreadyInUse';
    case EMAIL_NOT_VERIFIED = 'auth::status.warnings.emailNotVerified';
    case LOGOUT_SUCCESS = 'auth::status.success.loggedOut';
    case LOGIN_SUCCESS = 'auth::status.success.loggedIn';
    case REGISTER_SUCCESS = 'auth::status.success.registered';
}
