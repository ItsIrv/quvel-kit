import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('src/modules/Quvel/layouts/MainLayout.vue'),
    children: [{ path: '', component: () => import('src/modules/Quvel/pages/LandingPage.vue') }],
  },
  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('src/modules/Core/pages/ErrorNotFound.vue'),
  },
];

export default routes;
