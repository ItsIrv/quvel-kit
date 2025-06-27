import { RouteRecordRaw } from 'vue-router';
import MemberLayout from '../layouts/MemberLayout.vue';
import { DashboardRoutes } from './constants';

/**
 * Dashboard module routes
 * 
 * All dashboard routes require authentication by default.
 * Individual routes can override this with custom auth configuration.
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
    ],
  },
];

export default routes;