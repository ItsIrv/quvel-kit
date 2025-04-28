const OAuthStatusEnum = {
  INVALID_NONCE: 'auth.status.errors.invalidNonce',
  INVALID_TOKEN: 'auth.status.errors.invalidToken',
  INVALID_PROVIDER: 'auth.status.errors.invalidProvider',
  INVALID_USER: 'auth.status.errors.invalidUser',
  EMAIL_TAKEN: 'auth.status.errors.emailTaken',
  INVALID_CONFIG: 'auth.status.errors.invalidConfig',
  INTERNAL_ERROR: 'auth.status.errors.internalError',
  EMAIL_NOT_VERIFIED: 'auth.status.warnings.emailNotVerified',
  LOGIN_SUCCESS: 'auth.status.success.loginOk',
  USER_CREATED: 'auth.status.success.userCreated',
  CLIENT_TOKEN_GRANTED: 'auth.status.success.clientTokenGranted',
} as const;

type OAuthStatusEnum = (typeof OAuthStatusEnum)[keyof typeof OAuthStatusEnum];

const normalizeOAuthStatus = (status: string): OAuthStatusEnum => {
  return status.replace('auth::', 'auth.') as OAuthStatusEnum;
};

const mapStatusToType = (status: OAuthStatusEnum): 'positive' | 'warning' | 'negative' | 'info' => {
  const type = status.split('.')[2];

  switch (type) {
    case 'success':
      return 'positive';
    case 'warnings':
      return 'warning';
    case 'errors':
      return 'negative';
    default:
      return 'info';
  }
};

export { mapStatusToType, normalizeOAuthStatus, OAuthStatusEnum, OAuthStatusEnum as default };
