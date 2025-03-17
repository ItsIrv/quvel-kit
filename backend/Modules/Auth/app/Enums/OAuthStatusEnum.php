<?php

namespace Modules\Auth\Enums;

use App\Traits\TranslatableEnum;

enum OAuthStatusEnum: string
{
    use TranslatableEnum;

    case INVALID_NONCE = 'auth::status.errors.invalidNonce';
    case INVALID_TOKEN = 'auth::status.errors.invalidToken';
    case INVALID_PROVIDER = 'auth::status.errors.invalidProvider';
    case INVALID_USER = 'auth::status.errors.invalidUser';
    case EMAIL_TAKEN = 'auth::status.errors.emailTaken';
    case LOGIN_OK = 'auth::status.success.loginOk';
    case USER_CREATED = 'auth::status.success.userCreated';
    case EMAIL_NOT_VERIFIED = 'auth::status.warnings.emailNotVerified';
    case CLIENT_TOKEN_GRANTED = 'auth::status.success.clientTokenGranted';
    case INVALID_CONFIG = 'auth::status.errors.invalidConfig';
    case INTERNAL_ERROR = 'auth::status.errors.internalError';
}
