/**
 * Dashboard Module Route Constants
 */
export const DashboardRoutes = {
  DASHBOARD: 'dashboard',
  SETTINGS: 'dashboard.settings',
} as const;

export type DashboardRouteNames = typeof DashboardRoutes[keyof typeof DashboardRoutes];