import { RouteRecordRaw } from 'vue-router';
import MemberLayout from '../layouts/MemberLayout.vue';
import { DashboardRoutes } from './constants';

/**
 * Dashboard module routes
 * 
 * All dashboard routes require authentication by default.
 * Individual routes can override this with meta.requiresAuth or meta.skipAuth.
 */
const routes: RouteRecordRaw[] = [
  {
    path: '/dashboard',
    component: MemberLayout,
    children: [
      {
        path: '',
        name: DashboardRoutes.DASHBOARD,
        component: () => import('../pages/DashboardPage.vue'),
        meta: {
          title: 'dashboard.title',
          breadcrumbs: false, // Home breadcrumb only
        },
      },
      {
        path: 'projects',
        name: DashboardRoutes.PROJECTS,
        component: () => import('../pages/ProjectsPage.vue'),
        meta: {
          title: 'dashboard.nav.projects',
        },
      },
      {
        path: 'tasks',
        name: DashboardRoutes.TASKS, 
        component: () => import('../pages/TasksPage.vue'),
        meta: {
          title: 'dashboard.nav.tasks',
        },
      },
      {
        path: 'calendar',
        name: DashboardRoutes.CALENDAR,
        component: () => import('../pages/CalendarPage.vue'),
        meta: {
          title: 'dashboard.nav.calendar',
        },
      },
      {
        path: 'reports',
        name: DashboardRoutes.REPORTS,
        component: () => import('../pages/ReportsPage.vue'),
        meta: {
          title: 'dashboard.nav.reports',
        },
      },
      {
        path: 'analytics',
        name: DashboardRoutes.ANALYTICS,
        component: () => import('../pages/AnalyticsPage.vue'),
        meta: {
          title: 'dashboard.nav.analytics',
        },
      },
    ],
  },
];

export default routes;