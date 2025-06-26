/**
 * Dashboard Module Route Constants
 */
export const DashboardRoutes = {
  DASHBOARD: 'dashboard',
  PROJECTS: 'dashboard-projects',
  TASKS: 'dashboard-tasks',
  CALENDAR: 'dashboard-calendar',
  REPORTS: 'dashboard-reports',
  ANALYTICS: 'dashboard-analytics',
} as const;

export type DashboardRouteNames = typeof DashboardRoutes[keyof typeof DashboardRoutes];