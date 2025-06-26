/**
 * Quvel Module Route Constants
 */
export const QuvelRoutes = {
  LANDING: 'landing',
  PROFILE: 'profile',
  SETTINGS: 'settings',
} as const;

export type QuvelRouteNames = typeof QuvelRoutes[keyof typeof QuvelRoutes];