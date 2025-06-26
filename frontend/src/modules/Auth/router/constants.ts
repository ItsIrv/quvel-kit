/**
 * Auth Module Route Constants
 */
export const AuthRoutes = {
  LOGIN: 'login',
  SIGNUP: 'signup',
  PASSWORD_RESET: 'password-reset',
  PASSWORD_RESET_TOKEN: 'password-reset-token',
} as const;

export type AuthRouteNames = typeof AuthRoutes[keyof typeof AuthRoutes];