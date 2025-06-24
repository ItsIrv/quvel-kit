import type { RouteRecordRaw } from 'vue-router';
import { getModuleRoutes } from 'src/modules/moduleRegistry';

const routes: RouteRecordRaw[] = [
  ...getModuleRoutes(),
  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('src/modules/Core/pages/ErrorNotFound.vue'),
  },
];

export default routes;
