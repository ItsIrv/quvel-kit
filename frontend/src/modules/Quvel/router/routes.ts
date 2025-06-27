import { RouteRecordRaw } from 'vue-router';
import MainLayout from 'src/modules/Quvel/layouts/MainLayout.vue';
import { QuvelRoutes } from './constants';
import { createPublicAuth } from 'src/modules/Auth/utils/auth-meta';

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
          auth: createPublicAuth(),
        },
      },
    ],
  },
];

export default routes;
