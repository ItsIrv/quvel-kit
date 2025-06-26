/**
 * Auth Module Route Constants
 * 
 * Defines route names for authentication-related navigation and redirects.
 */

/**
 * Authentication route names
 */
export const AuthRoutes = {
  LOGIN: 'login',
  SIGNUP: 'signup',
  PASSWORD_RESET: 'password-reset',
  PASSWORD_RESET_TOKEN: 'password-reset-token',
} as const;

/**
 * Application route names
 * 
 * Main app routes used for authentication redirects and navigation.
 */
export const AppRoutes = {
  LANDING: 'landing',
  PROFILE: 'profile',
  SETTINGS: 'settings',
} as const;

/**
 * Type definitions for route name constants
 */
export type AuthRouteNames = typeof AuthRoutes[keyof typeof AuthRoutes];
export type AppRouteNames = typeof AppRoutes[keyof typeof AppRoutes];