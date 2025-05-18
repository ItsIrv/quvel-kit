import type { RouteRecordRaw } from 'vue-router';
import quvelRoutes from 'src/modules/Quvel/router/routes';

const routes: RouteRecordRaw[] = [
  ...quvelRoutes,
  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('src/modules/Core/pages/ErrorNotFound.vue'),
  },
];

export default routes;
