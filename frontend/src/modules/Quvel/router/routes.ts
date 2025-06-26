import { RouteRecordRaw } from 'vue-router';
import MainLayout from 'src/modules/Quvel/layouts/MainLayout.vue';

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
        name: 'landing',
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
