import { RouteRecordRaw } from 'vue-router';

/**
 * Quvel module routes
 */
const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('src/modules/Quvel/layouts/MainLayout.vue'),
    children: [
      {
        path: '',
        name: 'landing',
        component: () => import('src/modules/Quvel/pages/LandingPage.vue'),
        meta: {
          landerBackground: true,
          backgroundClass: 'LandingBackground',
        },
      },
    ],
  },
];

export default routes;
