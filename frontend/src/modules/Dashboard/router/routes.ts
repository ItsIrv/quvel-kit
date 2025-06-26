import { RouteRecordRaw } from 'vue-router';
import MemberLayout from '../layouts/MemberLayout.vue';

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
        name: 'dashboard',
        component: () => import('../pages/DashboardPage.vue'),
        meta: {
          title: 'dashboard.title',
          breadcrumbs: false, // Home breadcrumb only
        },
      },
      {
        path: 'projects',
        name: 'dashboard-projects',
        component: () => import('../pages/ProjectsPage.vue'),
        meta: {
          title: 'dashboard.nav.projects',
        },
      },
      {
        path: 'tasks',
        name: 'dashboard-tasks', 
        component: () => import('../pages/TasksPage.vue'),
        meta: {
          title: 'dashboard.nav.tasks',
        },
      },
      {
        path: 'calendar',
        name: 'dashboard-calendar',
        component: () => import('../pages/CalendarPage.vue'),
        meta: {
          title: 'dashboard.nav.calendar',
        },
      },
      {
        path: 'reports',
        name: 'dashboard-reports',
        component: () => import('../pages/ReportsPage.vue'),
        meta: {
          title: 'dashboard.nav.reports',
        },
      },
      {
        path: 'analytics',
        name: 'dashboard-analytics',
        component: () => import('../pages/AnalyticsPage.vue'),
        meta: {
          title: 'dashboard.nav.analytics',
        },
      },
    ],
  },
];

export default routes;