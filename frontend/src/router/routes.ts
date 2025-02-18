import type { RouteRecordRaw } from 'vue-router'

const routes: RouteRecordRaw[] = [
  {
    path: '/',
    component: () => import('layouts/LanderLayout.vue'),
    children: [{ path: '', component: () => import('pages/LandingPage.vue') }],
  },

  {
    path: '/welcome',
    component: () => import('layouts/LanderLayout.vue'),
    children: [{ path: '', component: () => import('pages/WelcomePage.vue') }],
  },

  // Always leave this as last one,
  // but you can also remove it
  {
    path: '/:catchAll(.*)*',
    component: () => import('pages/ErrorNotFound.vue'),
  },
]

export default routes
