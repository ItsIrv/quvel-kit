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
          backgroundClass: 'LandingBackground',
        },
      },
      {
        path: 'profile',
        name: 'profile',
        component: () => import('src/modules/Quvel/pages/ProfilePage.vue'),
        meta: {
          requiresAuth: true,
          title: 'quvel.profile.title',
        },
      },
      {
        path: 'settings',
        name: 'settings',
        component: () => import('src/modules/Quvel/pages/SettingsPage.vue'),
        meta: {
          requiresAuth: true,
          title: 'quvel.settings.title',
        },
      },
    ],
  },
];

export default routes;
