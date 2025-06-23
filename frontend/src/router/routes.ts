import type { RouteRecordRaw } from 'vue-router';
import { AuthModule } from 'src/modules/Auth';
import { NotificationsModule } from 'src/modules/Notifications';
import { QuvelModule } from 'src/modules/Quvel';

const routes: RouteRecordRaw[] = [
  ...(AuthModule.routes?.() || []),
  ...(NotificationsModule.routes?.() || []), 
  ...(QuvelModule.routes?.() || []),
  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('src/modules/Core/pages/ErrorNotFound.vue'),
  },
];

export default routes;
