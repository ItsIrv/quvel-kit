export const AuthStatusEnum = {
  USER_NOT_FOUND: 'auth::status.errors.userNotFound',
  INVALID_CREDENTIALS: 'auth::status.errors.invalidCredentials',
  EMAIL_ALREADY_IN_USE: 'auth::status.errors.emailAlreadyInUse',
  EMAIL_NOT_VERIFIED: 'auth::status.warnings.emailNotVerified',
  LOGOUT_SUCCESS: 'auth::status.success.loggedOut',
  LOGIN_SUCCESS: 'auth::status.success.loggedIn',
  REGISTER_SUCCESS: 'auth::status.success.registered',
} as const;

export type AuthStatusEnum = (typeof AuthStatusEnum)[keyof typeof AuthStatusEnum];
