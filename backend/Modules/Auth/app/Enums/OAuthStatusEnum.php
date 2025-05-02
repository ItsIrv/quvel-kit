<?php

namespace Modules\Auth\Enums;

use Modules\Core\Traits\TranslatableEnum;

enum OAuthStatusEnum: string
{
    use TranslatableEnum;

    case INVALID_NONCE        = 'auth::status.errors.invalidNonce';
    case INVALID_TOKEN        = 'auth::status.errors.invalidToken';
    case INVALID_PROVIDER     = 'auth::status.errors.invalidProvider';
    case INVALID_USER         = 'auth::status.errors.invalidUser';
    case EMAIL_TAKEN          = 'auth::status.errors.emailTaken';
    case INVALID_CONFIG       = 'auth::status.errors.invalidConfig';
    case INTERNAL_ERROR       = 'common::status.errors.internalError';
    case EMAIL_NOT_VERIFIED   = 'auth::status.warnings.emailNotVerified';
    case LOGIN_SUCCESS        = 'auth::status.success.loggedIn';
    case USER_CREATED         = 'auth::status.success.userCreated';
    case CLIENT_TOKEN_GRANTED = 'auth::status.success.clientTokenGranted';
}
