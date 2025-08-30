<?php

namespace Modules\Phone\Enums;

/**
 * Phone verification status messages.
 */
enum PhoneStatusEnum: string
{
    case VERIFICATION_SENT = 'Phone verification code sent successfully';
    case VERIFIED          = 'Phone number verified successfully';
    case INVALID_OTP       = 'Invalid or expired verification code';
    case EXPIRED_OTP       = 'Verification code has expired';
    case PHONE_REMOVED     = 'Phone number removed successfully';
    case PHONE_UNAVAILABLE = 'This phone number is already registered';
    case INVALID_PHONE     = 'Invalid phone number format';
    case RATE_LIMITED      = 'Too many verification attempts. Please try again later';
    case SMS_FAILED        = 'Failed to send verification code';
    case ALREADY_VERIFIED  = 'Phone number is already verified';
    case NO_PHONE          = 'No phone number associated with this account';
}
