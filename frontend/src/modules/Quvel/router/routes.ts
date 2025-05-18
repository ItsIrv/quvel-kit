export default [
  {
    path: '/',
    component: () => import('src/modules/Quvel/layouts/MainLayout.vue'),
    children: [{ path: '', component: () => import('src/modules/Quvel/pages/LandingPage.vue') }],
  },
];
