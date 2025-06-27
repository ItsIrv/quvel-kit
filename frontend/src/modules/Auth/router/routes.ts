import type { RouteRecordRaw } from 'vue-router';

/**
 * Auth Module Routes
 *
 * Authentication routes with guest-only access patterns.
 * Authenticated users will be redirected away from these pages.
 */
const routes: RouteRecordRaw[] = [
  // Example auth routes using the new auth system
  // Uncomment and customize as needed for your application
  // {
  //   path: '/auth',
  //   component: () => import('../layouts/AuthLayout.vue'),
  //   children: [
  //     {
  //       path: 'login',
  //       name: 'login',
  //       component: () => import('../pages/LoginPage.vue'),
  //       meta: {
  //         title: 'auth.login.title',
  //         auth: createGuestOnlyAuth(), // Redirect authenticated users to default success route
  //       },
  //     },
  //     {
  //       path: 'register',
  //       name: 'register',
  //       component: () => import('../pages/RegisterPage.vue'),
  //       meta: {
  //         title: 'auth.register.title',
  //         auth: createGuestOnlyAuth('/welcome'), // Redirect authenticated users to custom route
  //       },
  //     },
  //     {
  //       path: 'forgot-password',
  //       name: 'forgot-password',
  //       component: () => import('../pages/ForgotPasswordPage.vue'),
  //       meta: {
  //         title: 'auth.forgotPassword.title',
  //         auth: createGuestOnlyAuth(),
  //       },
  //     },
  //     {
  //       path: 'reset-password',
  //       name: 'reset-password',
  //       component: () => import('../pages/ResetPasswordPage.vue'),
  //       meta: {
  //         title: 'auth.resetPassword.title',
  //         auth: createSkipAuth(), // Skip auth entirely for password reset tokens
  //       },
  //     },
  //   ],
  // },
];

export default routes;
