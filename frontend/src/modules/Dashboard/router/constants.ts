/**
 * Dashboard Module Route Constants
 */
export const DashboardRoutes = {
  DASHBOARD: 'dashboard',
} as const;

export type DashboardRouteNames = typeof DashboardRoutes[keyof typeof DashboardRoutes];