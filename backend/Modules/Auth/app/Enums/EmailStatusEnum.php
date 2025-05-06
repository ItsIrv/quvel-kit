<?php

namespace Modules\Auth\Enums;

enum EmailStatusEnum: string
{
    case EMAIL_VERIFIED            = 'auth::status.success.emailVerified';
    case EMAIL_VERIFICATION_NOTICE = 'auth::status.success.emailVerificationNotice';
}
