<?php

namespace Modules\Auth\Enums;

enum OAuthStatusEnum: string
{
    case INVALID_NONCE       = 'auth::status.errors.invalidNonce';
    case INVALID_TOKEN       = 'auth::status.errors.invalidToken';
    case INVALID_PROVIDER    = 'auth::status.errors.invalidProvider';
    case INVALID_USER        = 'auth::status.errors.invalidUser';
    case EMAIL_TAKEN         = 'auth::status.errors.emailTaken';
    case PROVIDER_ID_TAKEN   = 'auth::status.errors.providerIdTaken';
    case LOGIN_OK            = 'auth::status.success.loginOk';
    case USER_CREATED        = 'auth::status.success.userCreated';
    case EMAIL_NOT_VERIFIED  = 'auth::status.warnings.emailNotVerified';
    case CLIENT_TOKEN_GRANED = 'auth::status.success.clientTokenGranted';
}
