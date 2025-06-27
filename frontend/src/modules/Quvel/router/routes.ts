import { RouteRecordRaw } from 'vue-router';
import MainLayout from 'src/modules/Quvel/layouts/MainLayout.vue';
import { QuvelRoutes } from './constants';

/**
 * Quvel module routes
 */
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: MainLayout,
    children: [
      {
        path: '',
        name: QuvelRoutes.LANDING,
        component: () => import('src/modules/Quvel/pages/LandingPage.vue'),
        meta: {
          backgroundClass: 'LandingBackground',
          requiresAuth: false,
        },
      },
    ],
  },
];

export default routes;
